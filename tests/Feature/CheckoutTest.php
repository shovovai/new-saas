<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_switching_to_a_free_plan_happens_instantly_with_no_gateway_involved(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $starter = Plan::where('slug', 'starter')->firstOrFail();

        $response = $this->actingAs($user)->post('/billing/checkout', [
            'plan_id' => $starter->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('billing.show'));
        $this->assertSame($starter->id, $team->activeSubscription()->plan_id);
    }

    public function test_switching_to_a_paid_plan_without_a_provider_shows_an_error(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');
        $professional = Plan::where('slug', 'professional')->firstOrFail();

        $response = $this->actingAs($user)->post('/billing/checkout', [
            'plan_id' => $professional->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('billing.show'));
        $response->assertSessionHas('error');
    }

    public function test_checkout_fails_gracefully_when_gateway_is_not_configured(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');
        $professional = Plan::where('slug', 'professional')->firstOrFail();

        $response = $this->actingAs($user)->post('/billing/checkout', [
            'plan_id' => $professional->id,
            'billing_cycle' => 'monthly',
            'provider' => 'stripe',
        ]);

        $response->assertRedirect(route('billing.show'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stripe is not configured', session('error'));
    }

    public function test_stripe_webhook_is_rejected_without_a_valid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        $response = $this->postJson('/webhooks/stripe', ['type' => 'checkout.session.completed']);

        $response->assertStatus(400);
    }

    public function test_stripe_webhook_activates_the_plan_with_a_valid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);

        [$user, $team] = $this->createUserWithTeam('starter');
        $professional = Plan::where('slug', 'professional')->firstOrFail();

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'subscription' => 'sub_test_123',
                'amount_total' => 2900,
                'currency' => 'usd',
                'metadata' => [
                    'team_id' => (string) $team->id,
                    'plan_id' => (string) $professional->id,
                    'billing_cycle' => 'monthly',
                ],
            ]],
        ]);

        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, 'whsec_test');

        $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertSame($professional->id, $team->activeSubscription()->plan_id);
        $this->assertDatabaseHas('invoices', ['team_id' => $team->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['team_id' => $team->id, 'status' => 'succeeded']);
    }
}
