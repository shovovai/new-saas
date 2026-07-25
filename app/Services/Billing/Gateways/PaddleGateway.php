<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\Team;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real integration against Paddle Billing's Transactions API
 * (https://developer.paddle.com/api-reference/transactions/overview).
 * Requires a `paddle_price_id_monthly`/`paddle_price_id_yearly` on the
 * Plan, created in the Paddle dashboard.
 */
class PaddleGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function name(): string
    {
        return 'paddle';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.paddle.api_key');
    }

    private function baseUrl(): string
    {
        return config('services.paddle.sandbox', true)
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    public function createCheckoutSession(Team $team, Plan $plan, string $billingCycle, string $successUrl, string $cancelUrl): string
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException('Paddle is not configured (set PADDLE_API_KEY).');
        }

        $priceId = $billingCycle === 'yearly' ? $plan->paddle_price_id_yearly : $plan->paddle_price_id_monthly;

        if (! $priceId) {
            throw new PaymentGatewayException("Plan \"{$plan->name}\" has no Paddle price configured for {$billingCycle} billing.");
        }

        $response = Http::withToken(config('services.paddle.api_key'))
            ->post($this->baseUrl().'/transactions', [
                'items' => [['price_id' => $priceId, 'quantity' => 1]],
                'customer' => ['email' => $team->owner->email],
                'checkout' => ['url' => $successUrl],
                'custom_data' => [
                    'team_id' => (string) $team->id,
                    'plan_id' => (string) $plan->id,
                    'billing_cycle' => $billingCycle,
                ],
            ]);

        if (! $response->successful()) {
            throw new PaymentGatewayException('Paddle transaction creation failed: '.$response->body());
        }

        $checkoutUrl = $response->json('data.checkout.url');

        if (! $checkoutUrl) {
            throw new PaymentGatewayException('Paddle did not return a checkout URL — confirm hosted checkout is enabled for this account.');
        }

        return $checkoutUrl;
    }

    public function handleWebhook(Request $request): void
    {
        $secret = config('services.paddle.webhook_secret');

        if (! $secret) {
            throw new PaymentGatewayException('Paddle webhook secret is not configured.');
        }

        $this->verifySignature($request, $secret);

        $event = $request->json()->all();

        match ($event['event_type'] ?? null) {
            'transaction.completed' => $this->onTransactionCompleted($event),
            default => Log::info('Unhandled Paddle webhook event', ['type' => $event['event_type'] ?? 'unknown']),
        };
    }

    private function verifySignature(Request $request, string $secret): void
    {
        $header = $request->header('Paddle-Signature', '');
        parse_str(str_replace(';', '&', $header), $parts);

        $timestamp = $parts['ts'] ?? null;
        $signature = $parts['h1'] ?? null;

        if (! $timestamp || ! $signature) {
            throw new PaymentGatewayException('Missing Paddle-Signature header.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}:{$request->getContent()}", $secret);

        if (! hash_equals($expected, $signature)) {
            throw new PaymentGatewayException('Paddle webhook signature verification failed.');
        }
    }

    private function onTransactionCompleted(array $event): void
    {
        $transaction = $event['data'] ?? [];
        $customData = $transaction['custom_data'] ?? [];

        $team = Team::find($customData['team_id'] ?? null);
        $plan = Plan::find($customData['plan_id'] ?? null);

        if (! $team || ! $plan) {
            Log::warning('Paddle transaction.completed missing team/plan custom_data', $customData);

            return;
        }

        $subscription = $this->subscriptions->changePlan($team, $plan, $customData['billing_cycle'] ?? 'monthly');
        $subscription->update([
            'provider' => 'paddle',
            'provider_subscription_id' => $transaction['subscription_id'] ?? $transaction['id'] ?? null,
        ]);

        $amount = (int) ($transaction['details']['totals']['total'] ?? ($customData['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly));

        $invoice = $team->invoices()->create([
            'subscription_id' => $subscription->id,
            'number' => 'INV-'.strtoupper(uniqid()),
            'amount' => $amount,
            'currency' => $transaction['currency_code'] ?? 'USD',
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        $team->payments()->create([
            'invoice_id' => $invoice->id,
            'provider' => 'paddle',
            'provider_payment_id' => $transaction['id'] ?? null,
            'amount' => $amount,
            'currency' => $transaction['currency_code'] ?? 'USD',
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
    }
}
