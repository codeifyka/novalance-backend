<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Proposal;
use App\Models\PayoutRequest;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Credit funds to a user's wallet via payment gateway.
     */
    public function deposit(
        User $user,
        float $amount,
        string $provider = 'stripe',
        array $providerData = [],
        ?string $description = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception("Deposit amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $amount, $provider, $providerData, $description) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = $user->getOrCreateWallet();
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            $wallet->balance = (float)$wallet->balance + $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $amount,
                'direction' => 'credit',
                'provider' => $provider,
                'provider_data' => $providerData,
                'status' => 'completed',
                'description' => $description ?: "Deposit via {$provider}",
            ]);
        });
    }

    /**
     * Lock funds from client's wallet into escrow for a specific proposal.
     */
    public function lockEscrow(User $client, Proposal $proposal): WalletTransaction
    {
        $amount = (float) $proposal->bid;
        if ($amount <= 0) {
            throw new Exception("Proposal bid amount must be greater than zero.");
        }

        return DB::transaction(function () use ($client, $proposal, $amount) {
            $wallet = Wallet::where('user_id', $client->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = $client->getOrCreateWallet();
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            if ((float)$wallet->balance < $amount) {
                $deficit = $amount - (float)$wallet->balance;
                throw new Exception("Insufficient wallet balance. Required: \${$amount}, Available: \${$wallet->balance}. Deficit: \${$deficit}");
            }

            $wallet->balance = (float)$wallet->balance - $amount;
            $wallet->save();

            $jobTitle = $proposal->job_post ? $proposal->job_post->title : "Job #{$proposal->job_post_id}";

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'escrow_lock',
                'amount' => $amount,
                'direction' => 'debit',
                'source_type' => Proposal::class,
                'source_id' => $proposal->id,
                'status' => 'completed',
                'description' => "Escrow locked for job: {$jobTitle}",
            ]);
        });
    }

    const PLATFORM_FEE_PERCENTAGE = 0.10; // 10% service fee

    /**
     * Release escrowed funds to freelancer wallet upon contract completion (minus 10% admin platform fee).
     */
    public function releaseEscrow(Proposal $proposal): WalletTransaction
    {
        $totalBid = (float) $proposal->bid;

        return DB::transaction(function () use ($proposal, $totalBid) {
            $freelancer = $proposal->freelancer;
            if (!$freelancer) {
                throw new Exception("Freelancer not found for this proposal.");
            }

            $fee = round($totalBid * self::PLATFORM_FEE_PERCENTAGE, 2);
            $freelancerEarnings = round($totalBid - $fee, 2);

            $wallet = Wallet::where('user_id', $freelancer->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = $freelancer->getOrCreateWallet();
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            $wallet->balance = (float)$wallet->balance + $freelancerEarnings;
            $wallet->save();

            $proposal->status = 'done';
            $proposal->save();

            $jobTitle = $proposal->job_post ? $proposal->job_post->title : "Job #{$proposal->job_post_id}";

            // 1. Transaction for Freelancer
            $freelancerTx = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'escrow_release',
                'amount' => $freelancerEarnings,
                'direction' => 'credit',
                'source_type' => Proposal::class,
                'source_id' => $proposal->id,
                'status' => 'completed',
                'description' => "Earnings released for job: {$jobTitle} (after 10% service fee of \${$fee})",
            ]);

            // 2. Transaction for Admin Platform Fee
            $this->creditPlatformFee($fee, $proposal, $jobTitle);

            return $freelancerTx;
        });
    }

    /**
     * Credit platform fee to the system administrator wallet.
     */
    protected function creditPlatformFee(float $fee, Proposal $proposal, string $jobTitle): ?WalletTransaction
    {
        if ($fee <= 0) return null;

        $admin = User::where('account_type', 'admin')->first();
        if (!$admin) return null;

        $adminWallet = Wallet::where('user_id', $admin->id)->lockForUpdate()->first();
        if (!$adminWallet) {
            $adminWallet = $admin->getOrCreateWallet();
            $adminWallet = Wallet::where('id', $adminWallet->id)->lockForUpdate()->first();
        }

        $adminWallet->balance = (float)$adminWallet->balance + $fee;
        $adminWallet->save();

        return WalletTransaction::create([
            'wallet_id' => $adminWallet->id,
            'type' => 'platform_fee',
            'amount' => $fee,
            'direction' => 'credit',
            'source_type' => Proposal::class,
            'source_id' => $proposal->id,
            'status' => 'completed',
            'description' => "10% Platform fee collected for job: {$jobTitle}",
        ]);
    }

    /**
     * Refund escrow funds in full or partially (used during dispute resolution or proposal failure).
     */
    public function refundEscrow(
        Proposal $proposal,
        float $clientRefundAmount,
        float $freelancerAmount = 0.0,
        ?string $reason = null
    ): array {
        $totalRequested = round($clientRefundAmount + $freelancerAmount, 2);
        $bidAmount = (float) $proposal->bid;

        if ($totalRequested > $bidAmount) {
            throw new Exception("Total resolution amount (\${$totalRequested}) exceeds contract bid (\${$bidAmount}).");
        }

        return DB::transaction(function () use ($proposal, $clientRefundAmount, $freelancerAmount, $bidAmount, $reason) {
            $transactions = [];
            $jobTitle = $proposal->job_post ? $proposal->job_post->title : "Job #{$proposal->job_post_id}";

            // 1. Credit Client if refund amount > 0 (100% of client portion refunded without fee)
            if ($clientRefundAmount > 0) {
                $client = $proposal->job_post ? $proposal->job_post->user : null;
                if ($client) {
                    $clientWallet = Wallet::where('user_id', $client->id)->lockForUpdate()->first() ?: $client->getOrCreateWallet();
                    $clientWallet->balance = (float)$clientWallet->balance + $clientRefundAmount;
                    $clientWallet->save();

                    $transactions[] = WalletTransaction::create([
                        'wallet_id' => $clientWallet->id,
                        'type' => 'escrow_refund',
                        'amount' => $clientRefundAmount,
                        'direction' => 'credit',
                        'source_type' => Proposal::class,
                        'source_id' => $proposal->id,
                        'status' => 'completed',
                        'description' => "Refund for job: {$jobTitle}" . ($reason ? " ({$reason})" : ""),
                    ]);
                }
            }

            // 2. Credit Freelancer if partial amount > 0 (minus 10% fee on their earned portion)
            if ($freelancerAmount > 0) {
                $freelancer = $proposal->freelancer;
                if ($freelancer) {
                    $fee = round($freelancerAmount * self::PLATFORM_FEE_PERCENTAGE, 2);
                    $netFreelancer = round($freelancerAmount - $fee, 2);

                    $freelancerWallet = Wallet::where('user_id', $freelancer->id)->lockForUpdate()->first() ?: $freelancer->getOrCreateWallet();
                    $freelancerWallet->balance = (float)$freelancerWallet->balance + $netFreelancer;
                    $freelancerWallet->save();

                    $transactions[] = WalletTransaction::create([
                        'wallet_id' => $freelancerWallet->id,
                        'type' => 'escrow_release',
                        'amount' => $netFreelancer,
                        'direction' => 'credit',
                        'source_type' => Proposal::class,
                        'source_id' => $proposal->id,
                        'status' => 'completed',
                        'description' => "Partial payment for job: {$jobTitle} (after 10% fee of \${$fee})",
                    ]);

                    $adminTx = $this->creditPlatformFee($fee, $proposal, $jobTitle);
                    if ($adminTx) {
                        $transactions[] = $adminTx;
                    }
                }
            }

            // 3. Update proposal status
            if ($clientRefundAmount >= $bidAmount) {
                $proposal->status = 'failed';
            } else {
                $proposal->status = 'done';
            }
            $proposal->save();

            return $transactions;
        });
    }

    /**
     * Submit a payout request from available wallet balance.
     */
    public function requestPayout(
        User $user,
        float $amount,
        string $provider = 'stripe',
        array $accountDetails = []
    ): PayoutRequest {
        if ($amount <= 0) {
            throw new Exception("Payout amount must be greater than zero.");
        }

        return DB::transaction(function () use ($user, $amount, $provider, $accountDetails) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = $user->getOrCreateWallet();
                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            }

            if ((float)$wallet->balance < $amount) {
                throw new Exception("Insufficient available balance for payout. Available: \${$wallet->balance}");
            }

            // Deduct immediately so balance cannot be double-spent
            $wallet->balance = (float)$wallet->balance - $amount;
            $wallet->save();

            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'provider' => $provider,
                'provider_data' => $accountDetails,
                'status' => 'pending',
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'payout',
                'amount' => $amount,
                'direction' => 'debit',
                'source_type' => PayoutRequest::class,
                'source_id' => $payoutRequest->id,
                'status' => 'pending',
                'description' => "Payout request via {$provider}",
            ]);

            return $payoutRequest;
        });
    }

    /**
     * Get user's wallet summary including available balance, computed escrow balance, and transactions.
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = $user->getOrCreateWallet();

        // Compute escrow balance
        $escrowBalance = 0.0;
        if ($user->account_type === 'client') {
            // Active proposals funded by this client
            $escrowBalance = (float) Proposal::whereHas('job_post', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->whereIn('status', ['active', 'disputed'])->sum('bid');
        } elseif ($user->account_type === 'freelancer') {
            // Active proposals assigned to this freelancer
            $escrowBalance = (float) Proposal::where('freelancer_id', $user->id)
                ->whereIn('status', ['active', 'disputed'])
                ->sum('bid');
        }

        return [
            'wallet_id' => $wallet->id,
            'balance' => (float) $wallet->balance,
            'escrow_balance' => (float) $escrowBalance,
            'transactions' => $wallet->transactions()->take(30)->get(),
        ];
    }
}
