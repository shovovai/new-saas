<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\Team;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Real integration against the SSLCommerz Session API
 * (https://developer.sslcommerz.com/doc/v4/) — a payment gateway
 * commonly used in Bangladesh. Team/plan/billing-cycle are round-tripped
 * through SSLCommerz's custom value_a/value_b/value_c fields, which it
 * echoes back on the IPN callback.
 */
class SslcommerzGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function name(): string
    {
        return 'sslcommerz';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.sslcommerz.store_id');
    }

    private function baseUrl(): string
    {
        return config('services.sslcommerz.sandbox', true)
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    public function createCheckoutSession(Team $team, Plan $plan, string $billingCycle, string $successUrl, string $cancelUrl): string
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException('SSLCommerz is not configured (set SSLCOMMERZ_STORE_ID).');
        }

        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $transactionId = 'sgai-'.$team->id.'-'.Str::random(10);

        $response = Http::asForm()->post($this->baseUrl().'/gwprocess/v4/api.php', [
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'total_amount' => number_format($amount / 100, 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => $successUrl,
            'fail_url' => $cancelUrl,
            'cancel_url' => $cancelUrl,
            'ipn_url' => route('billing.webhook', ['provider' => 'sslcommerz']),
            'shipping_method' => 'NO',
            'product_name' => "SiteGuardian AI — {$plan->name} plan ({$billingCycle})",
            'product_category' => 'SaaS Subscription',
            'product_profile' => 'general',
            'cus_name' => $team->owner->name,
            'cus_email' => $team->owner->email,
            'cus_add1' => 'N/A',
            'cus_city' => 'N/A',
            'cus_postcode' => '0000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => 'N/A',
            'value_a' => (string) $team->id,
            'value_b' => (string) $plan->id,
            'value_c' => $billingCycle,
        ]);

        $data = $response->json();

        if (! $response->successful() || ($data['status'] ?? null) !== 'SUCCESS' || empty($data['GatewayPageURL'])) {
            throw new PaymentGatewayException('SSLCommerz session initiation failed: '.$response->body());
        }

        return $data['GatewayPageURL'];
    }

    public function handleWebhook(Request $request): void
    {
        $valId = $request->input('val_id');

        if (! $valId) {
            throw new PaymentGatewayException('Missing val_id in SSLCommerz IPN payload.');
        }

        $validation = Http::get($this->baseUrl().'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'format' => 'json',
        ])->json();

        if (! in_array($validation['status'] ?? null, ['VALID', 'VALIDATED'], true)) {
            throw new PaymentGatewayException('SSLCommerz IPN validation failed.');
        }

        $team = Team::find($validation['value_a'] ?? $request->input('value_a'));
        $plan = Plan::find($validation['value_b'] ?? $request->input('value_b'));
        $billingCycle = $validation['value_c'] ?? $request->input('value_c', 'monthly');

        if (! $team || ! $plan) {
            Log::warning('SSLCommerz IPN missing team/plan value fields', $validation);

            return;
        }

        $subscription = $this->subscriptions->changePlan($team, $plan, $billingCycle);
        $subscription->update([
            'provider' => 'sslcommerz',
            'provider_subscription_id' => $validation['tran_id'] ?? null,
        ]);

        $amount = (int) round((float) ($validation['amount'] ?? 0) * 100);

        $invoice = $team->invoices()->create([
            'subscription_id' => $subscription->id,
            'number' => 'INV-'.strtoupper(uniqid()),
            'amount' => $amount,
            'currency' => $validation['currency_type'] ?? 'BDT',
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        $team->payments()->create([
            'invoice_id' => $invoice->id,
            'provider' => 'sslcommerz',
            'provider_payment_id' => $validation['bank_tran_id'] ?? $validation['tran_id'] ?? null,
            'amount' => $amount,
            'currency' => $validation['currency_type'] ?? 'BDT',
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
    }
}
