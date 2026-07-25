<?php

namespace App\Services\Billing;

use App\Services\Billing\Gateways\PaddleGateway;
use App\Services\Billing\Gateways\PaymentGatewayException;
use App\Services\Billing\Gateways\PaymentGatewayInterface;
use App\Services\Billing\Gateways\SslcommerzGateway;
use App\Services\Billing\Gateways\StripeGateway;

class PaymentGatewayManager
{
    /**
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    private array $gateways = [
        'stripe' => StripeGateway::class,
        'paddle' => PaddleGateway::class,
        'sslcommerz' => SslcommerzGateway::class,
    ];

    public function gateway(string $name): PaymentGatewayInterface
    {
        if (! isset($this->gateways[$name])) {
            throw new PaymentGatewayException("Unknown payment gateway \"{$name}\".");
        }

        return app($this->gateways[$name]);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys($this->gateways);
    }
}
