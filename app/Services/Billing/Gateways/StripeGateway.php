<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\Team;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real integration against Stripe's Checkout Sessions REST API — no SDK
 * dependency, just documented HTTP calls
 * (https://docs.stripe.com/api/checkout/sessions). Requires a
 * `stripe_price_id_monthly`/`stripe_price_id_yearly` on the Plan (set from
 * the admin panel — Stripe Prices are created in the Stripe Dashboard or
 * API, this app only references their IDs).
 */
class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function name(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    public function createCheckoutSession(Team $team, Plan $plan, string $billingCycle, string $successUrl, string $cancelUrl): string
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException('Stripe is not configured (set STRIPE_SECRET).');
        }

        $priceId = $billingCycle === 'yearly' ? $plan->stripe_price_id_yearly : $plan->stripe_price_id_monthly;

        if (! $priceId) {
            throw new PaymentGatewayException("Plan \"{$plan->name}\" has no Stripe price configured for {$billingCycle} billing.");
        }

        $response = Http::asForm()->withToken(config('services.stripe.secret'))
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'subscription',
                'line_items' => [['price' => $priceId, 'quantity' => 1]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $team->id,
                'customer_email' => $team->owner->email,
                'metadata' => [
                    'team_id' => $team->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,
                ],
            ]);

        if (! $response->successful()) {
            throw new PaymentGatewayException('Stripe checkout session creation failed: '.$response->body());
        }

        return $response->json('url');
    }

    public function handleWebhook(Request $request): void
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            throw new PaymentGatewayException('Stripe webhook secret is not configured.');
        }

        $this->verifySignature($request, $secret);

        $event = $request->json()->all();

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event),
            default => Log::info('Unhandled Stripe webhook event', ['type' => $event['type'] ?? 'unknown']),
        };
    }

    private function verifySignature(Request $request, string $secret): void
    {
        $header = $request->header('Stripe-Signature', '');
        parse_str(str_replace(',', '&', $header), $parts);

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (! $timestamp || ! $signature) {
            throw new PaymentGatewayException('Missing Stripe-Signature header.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$request->getContent()}", $secret);

        if (! hash_equals($expected, $signature)) {
            throw new PaymentGatewayException('Stripe webhook signature verification failed.');
        }
    }

    private function onCheckoutCompleted(array $event): void
    {
        $session = $event['data']['object'] ?? [];
        $metadata = $session['metadata'] ?? [];

        $team = Team::find($metadata['team_id'] ?? null);
        $plan = Plan::find($metadata['plan_id'] ?? null);

        if (! $team || ! $plan) {
            Log::warning('Stripe checkout.session.completed missing team/plan metadata', $metadata);

            return;
        }

        $subscription = $this->subscriptions->changePlan($team, $plan, $metadata['billing_cycle'] ?? 'monthly');
        $subscription->update([
            'provider' => 'stripe',
            'provider_subscription_id' => $session['subscription'] ?? null,
        ]);

        $amount = $session['amount_total'] ?? ($metadata['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly);

        $invoice = $team->invoices()->create([
            'subscription_id' => $subscription->id,
            'number' => 'INV-'.strtoupper(uniqid()),
            'amount' => $amount,
            'currency' => strtoupper($session['currency'] ?? 'usd'),
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        $team->payments()->create([
            'invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'provider_payment_id' => $session['payment_intent'] ?? $session['id'] ?? null,
            'amount' => $amount,
            'currency' => strtoupper($session['currency'] ?? 'usd'),
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
    }
}
