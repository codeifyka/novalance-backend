<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminPayoutController extends Controller
{
    /**
     * List all payout requests for admin.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $query = PayoutRequest::with('user')->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $payouts = $query->get();

        return response()->json(['data' => $payouts], 200);
    }

    /**
     * Approve and execute payout via payment gateway strategy.
     */
    public function process(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $payoutRequest = PayoutRequest::with('user')->find($id);
        if (!$payoutRequest) {
            return response()->json(['error' => 'Payout request not found.'], 404);
        }

        if ($payoutRequest->status !== 'pending') {
            return response()->json(['error' => "Payout request is already {$payoutRequest->status}."], 400);
        }

        $gatewayManager = app(PaymentGatewayManager::class);
        $driver = $gatewayManager->driver($payoutRequest->provider);

        try {
            $result = $driver->processPayout($payoutRequest);

            return DB::transaction(function () use ($payoutRequest, $result, $request) {
                $payoutRequest->status = 'completed';
                $payoutRequest->transaction_reference = $result['transaction_reference'] ?? null;
                $payoutRequest->admin_notes = $request->admin_notes ?: 'Payout processed by admin.';
                $payoutRequest->processed_at = now();
                if (!empty($result['provider_data'])) {
                    $payoutRequest->provider_data = array_merge($payoutRequest->provider_data ?: [], $result['provider_data']);
                }
                $payoutRequest->save();

                // Update associated pending debit transaction
                WalletTransaction::where('source_type', PayoutRequest::class)
                    ->where('source_id', $payoutRequest->id)
                    ->update([
                        'status' => 'completed',
                        'provider_data' => $payoutRequest->provider_data,
                    ]);

                return response()->json([
                    'message' => 'Payout processed successfully.',
                    'data' => $payoutRequest,
                ], 200);
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject payout request and refund amount to freelancer wallet.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $payoutRequest = PayoutRequest::with('user')->find($id);
        if (!$payoutRequest) {
            return response()->json(['error' => 'Payout request not found.'], 404);
        }

        if ($payoutRequest->status !== 'pending') {
            return response()->json(['error' => "Payout request is already {$payoutRequest->status}."], 400);
        }

        $reason = $request->admin_notes ?: 'Payout request rejected by administrator.';

        return DB::transaction(function () use ($payoutRequest, $reason) {
            // Refund amount back to freelancer wallet
            $freelancer = $payoutRequest->user;
            $wallet = Wallet::where('user_id', $freelancer->id)->lockForUpdate()->first() ?: $freelancer->getOrCreateWallet();
            $wallet->balance = (float)$wallet->balance + (float)$payoutRequest->amount;
            $wallet->save();

            $payoutRequest->status = 'rejected';
            $payoutRequest->admin_notes = $reason;
            $payoutRequest->processed_at = now();
            $payoutRequest->save();

            // Record refund transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $payoutRequest->amount,
                'direction' => 'credit',
                'source_type' => PayoutRequest::class,
                'source_id' => $payoutRequest->id,
                'status' => 'completed',
                'description' => "Payout refund: {$reason}",
            ]);

            return response()->json([
                'message' => 'Payout rejected and funds returned to freelancer wallet.',
                'data' => $payoutRequest,
            ], 200);
        });
    }
}
