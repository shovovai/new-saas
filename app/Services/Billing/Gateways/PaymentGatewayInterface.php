<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\Team;
use Illuminate\Http\Request;

/**
 * Real, provider-specific checkout + webhook handling. Each implementation
 * makes genuine API calls to the named provider — when the provider's
 * credentials aren't configured, methods throw PaymentGatewayException
 * with a clear message rather than silently no-op'ing, exactly like
 * AiProviderInterface without an API key.
 */
interface PaymentGatewayInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * @return string URL to redirect the user to in order to complete checkout
     */
    public function createCheckoutSession(Team $team, Plan $plan, string $billingCycle, string $successUrl, string $cancelUrl): string;

    /**
     * Verifies the incoming webhook signature and applies the event
     * (mark invoice/payment paid, activate/cancel subscription, etc).
     * Throws PaymentGatewayException on an invalid signature.
     */
    public function handleWebhook(Request $request): void;
}
