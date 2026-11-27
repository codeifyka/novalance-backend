<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    protected WalletService $walletService;
    protected PaymentGatewayManager $gatewayManager;

    public function __construct(WalletService $walletService, PaymentGatewayManager $gatewayManager)
    {
        $this->walletService = $walletService;
        $this->gatewayManager = $gatewayManager;
    }

    public function getConfig(): JsonResponse
    {
        $driver = $this->gatewayManager->driver('stripe');
        $isConfigured = method_exists($driver, 'isConfigured') ? $driver->isConfigured() : false;
        $publishableKey = method_exists($driver, 'getPublishableKey') ? $driver->getPublishableKey() : null;

        return response()->json([
            'data' => [
                'provider' => 'stripe',
                'stripe_configured' => $isConfigured,
                'publishable_key' => $publishableKey,
                'mode' => $isConfigured ? 'stripe' : 'test_simulation',
            ],
        ], 200);
    }

    /**
     * Get current user's wallet overview (spendable balance, in-escrow balance, recent transactions).
     */
    public function getMyWallet(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $summary = $this->walletService->getWalletSummary($user);

        return response()->json(['data' => $summary], 200);
    }

    /**
     * Create a Stripe deposit intent for wallet top-up.
     */
    public function depositIntent(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'provider' => 'nullable|string',
        ]);

        $amount = (float) $request->amount;
        $provider = $request->provider ?: 'stripe';

        try {
            $driver = $this->gatewayManager->driver($provider);
            $intent = $driver->createDepositIntent($amount, $user, [
                'type' => 'wallet_deposit',
            ]);

            return response()->json(['data' => $intent], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm deposit after Stripe client-side payment confirmation.
     */
    public function confirmDeposit(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'payment_id' => 'required|string',
            'amount' => 'required|numeric|min:0.5',
            'provider' => 'nullable|string',
        ]);

        $provider = $request->provider ?: 'stripe';
        $amount = (float) $request->amount;

        try {
            // Idempotency guard: prevent confirming the same PaymentIntent more than once
            $alreadyProcessed = WalletTransaction::where('type', 'deposit')
                ->where('status', 'completed')
                ->where('provider_data->stripe_payment_intent_id', $request->payment_id)
                ->exists();

            if ($alreadyProcessed) {
                return response()->json(['error' => 'This payment has already been credited to your wallet.'], 409);
            }

            $driver = $this->gatewayManager->driver($provider);
            $verification = $driver->confirmDeposit($request->payment_id);

            if (empty($verification['succeeded'])) {
                return response()->json(['error' => 'Payment has not been completed.'], 400);
            }

            $depositAmount = $verification['amount'] > 0 ? (float)$verification['amount'] : $amount;

            $transaction = $this->walletService->deposit(
                $user,
                $depositAmount,
                $provider,
                $verification['provider_data'] ?? [],
                "Wallet top-up via {$provider}"
            );

            $summary = $this->walletService->getWalletSummary($user);

            return response()->json([
                'message' => 'Deposit successful.',
                'data' => [
                    'transaction' => $transaction,
                    'wallet' => $summary,
                ],
                'transaction' => $transaction,
                'wallet' => $summary,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Freelancer submits a payout request from available balance.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'provider' => 'nullable|string',
            'account_details' => 'nullable|array',
        ]);

        $amount = (float) $request->amount;
        $provider = $request->provider ?: 'stripe';
        $details = $request->account_details ?: [];

        try {
            $payout = $this->walletService->requestPayout($user, $amount, $provider, $details);

            return response()->json([
                'message' => 'Payout request submitted successfully.',
                'data' => $payout,
                'wallet' => $this->walletService->getWalletSummary($user),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
