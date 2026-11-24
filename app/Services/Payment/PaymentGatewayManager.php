<?php

namespace App\Services\Payment;

use InvalidArgumentException;

class PaymentGatewayManager
{
    protected array $drivers = [];

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: config('services.payment.default', 'stripe');

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    protected function resolve(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'stripe' => app(StripeGatewayService::class),
            default => throw new InvalidArgumentException("Unsupported payment driver [{$name}]."),
        };
    }
}
