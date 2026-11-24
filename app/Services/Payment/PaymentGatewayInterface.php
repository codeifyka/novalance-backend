<?php

namespace App\Services\Payment;

use App\Models\User;
use App\Models\PayoutRequest;

interface PaymentGatewayInterface
{
    /**
     * Get the identifier name of the provider.
     */
    public function getProviderName(): string;

    /**
     * Create a deposit intent / payment session.
     * Returns an array with client_secret, payment_id, status, and provider_data.
     */
    public function createDepositIntent(float $amount, User $user, array $metadata = []): array;

    /**
     * Confirm / verify a deposit by payment ID.
     * Returns an array with status ('succeeded' / 'failed'), amount, currency, and provider_data.
     */
    public function confirmDeposit(string $paymentIntentId): array;

    /**
     * Process a payout to a recipient.
     * Returns an array with status ('completed' / 'failed'), transaction_reference, and provider_data.
     */
    public function processPayout(PayoutRequest $payoutRequest): array;
}
