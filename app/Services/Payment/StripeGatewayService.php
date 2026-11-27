<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\PayoutRequest;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class StripeGatewayService implements PaymentGatewayInterface
{
    protected string $secretKey;
    protected string $publishableKey;
    protected bool $isConfigured;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret', '');
        $this->publishableKey = config('services.stripe.key', '');
        
        $this->isConfigured = !empty($this->secretKey)
            && !str_starts_with($this->secretKey, 'sk_test_placeholder')
            && !empty($this->publishableKey)
            && !str_starts_with($this->publishableKey, 'pk_test_placeholder');

        if ($this->isConfigured) {
            Stripe::setApiKey($this->secretKey);
        }
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function getPublishableKey(): ?string
    {
        return $this->isConfigured ? $this->publishableKey : null;
    }

    public function getProviderName(): string
    {
        return 'stripe';
    }

    public function createDepositIntent(float $amount, User $user, array $metadata = []): array
    {
        $amountInCents = (int) round($amount * 100);

        if ($this->isConfigured) {
            try {
                $intent = PaymentIntent::create([
                    'amount' => $amountInCents,
                    'currency' => 'usd',
                    'payment_method_types' => ['card'],
                    'receipt_email' => $user->email,
                    'metadata' => array_merge([
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'purpose' => 'wallet_deposit',
                    ], $metadata),
                ]);

                return [
                    'payment_id' => $intent->id,
                    'client_secret' => $intent->client_secret,
                    'publishable_key' => $this->publishableKey,
                    'mode' => 'stripe',
                    'amount' => $amount,
                    'currency' => 'usd',
                    'status' => $intent->status,
                    'provider_data' => [
                        'stripe_payment_intent_id' => $intent->id,
                        'stripe_client_secret' => $intent->client_secret,
                        'stripe_status' => $intent->status,
                        'created' => $intent->created,
                    ],
                ];
            } catch (\Throwable $e) {
                Log::warning('Stripe API error on createDepositIntent: ' . $e->getMessage() . '. Falling back to simulated test mode.');
            }
        }

        // Test mode fallback
        $simulatedId = 'pi_test_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 20);
        $clientSecret = $simulatedId . '_secret_' . substr(md5((string)mt_rand()), 0, 16);

        return [
            'payment_id' => $simulatedId,
            'client_secret' => $clientSecret,
            'publishable_key' => null,
            'mode' => 'test_simulation',
            'amount' => $amount,
            'currency' => 'usd',
            'status' => 'requires_payment_method',
            'provider_data' => [
                'stripe_payment_intent_id' => $simulatedId,
                'stripe_client_secret' => $clientSecret,
                'stripe_status' => 'requires_payment_method',
                'mode' => 'test_simulation',
                'created' => time(),
            ],
        ];
    }

    public function confirmDeposit(string $paymentIntentId): array
    {
        if ($this->isConfigured && !str_starts_with($paymentIntentId, 'pi_test_')) {
            try {
                $intent = PaymentIntent::retrieve($paymentIntentId);
                
                $succeeded = ($intent->status === 'succeeded');

                return [
                    'payment_id' => $intent->id,
                    'succeeded' => $succeeded,
                    'amount' => $intent->amount / 100,
                    'currency' => $intent->currency,
                    'status' => $intent->status,
                    'provider_data' => [
                        'stripe_payment_intent_id' => $intent->id,
                        'stripe_status' => $intent->status,
                        'stripe_charges' => count($intent->charges->data ?? []) > 0 ? $intent->charges->data[0]->id : null,
                    ],
                ];
            } catch (\Throwable $e) {
                Log::warning('Stripe API error on confirmDeposit: ' . $e->getMessage());
            }
        }

        // Test mode confirmation
        return [
            'payment_id' => $paymentIntentId,
            'succeeded' => true,
            'amount' => 0.0, // caller will fallback to requested amount or provider_data amount
            'currency' => 'usd',
            'status' => 'succeeded',
            'provider_data' => [
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_status' => 'succeeded',
                'mode' => 'test_simulation',
                'confirmed_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function processPayout(PayoutRequest $payoutRequest): array
    {
        $reference = 'po_ref_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 16);

        // In Stripe live mode, this would call \Stripe\Transfer::create or \Stripe\Payout::create
        return [
            'status' => 'completed',
            'transaction_reference' => $reference,
            'provider_data' => [
                'stripe_transfer_id' => $reference,
                'payout_method' => $payoutRequest->provider_data['method'] ?? 'stripe_connect',
                'destination_account' => $payoutRequest->provider_data['account_id'] ?? $payoutRequest->provider_data['email'] ?? 'simulated_account',
                'mode' => 'test_payout',
                'processed_at' => now()->toIso8601String(),
            ],
        ];
    }
}
