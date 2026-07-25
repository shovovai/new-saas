<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Billing\Gateways\PaymentGatewayException;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Starts a real checkout with the chosen provider for a paid plan, or
     * switches instantly for a free ($0) plan — no gateway involved.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermissionTo('billing.manage')) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'provider' => ['nullable', 'in:stripe,paddle,sslcommerz'],
        ]);

        $team = $request->user()->currentTeam;
        $plan = Plan::findOrFail($validated['plan_id']);
        $amount = $validated['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        if ($amount === 0) {
            $this->subscriptions->changePlan($team, $plan, $validated['billing_cycle']);

            return redirect()->route('billing.show')->with('success', "Plan changed to {$plan->name}.");
        }

        if (empty($validated['provider'])) {
            return redirect()->route('billing.show')->with('error', 'Choose a payment provider to continue.');
        }

        try {
            $gateway = $this->gateways->gateway($validated['provider']);

            $checkoutUrl = $gateway->createCheckoutSession(
                $team,
                $plan,
                $validated['billing_cycle'],
                route('billing.show', ['checkout' => 'success']),
                route('billing.show', ['checkout' => 'cancelled']),
            );
        } catch (PaymentGatewayException $e) {
            return redirect()->route('billing.show')->with('error', $e->getMessage());
        }

        return redirect()->away($checkoutUrl);
    }

    /**
     * Webhook/IPN receiver for all three gateways. Unauthenticated by
     * design (called by the payment provider, not a logged-in user) —
     * each gateway verifies its own signature before applying the event.
     */
    public function webhook(Request $request, string $provider): Response
    {
        try {
            $this->gateways->gateway($provider)->handleWebhook($request);
        } catch (PaymentGatewayException $e) {
            Log::warning("Payment webhook rejected for {$provider}", ['error' => $e->getMessage()]);

            return response('Webhook rejected: '.$e->getMessage(), 400);
        }

        return response('OK', 200);
    }
}
