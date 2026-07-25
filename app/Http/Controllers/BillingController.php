<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function show(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Billing/Show', [
            'team' => $team,
            'subscription' => $team->activeSubscription()?->load('plan'),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'invoices' => $team->invoices()->latest()->limit(20)->get(),
        ]);
    }

    public function changePlan(Request $request): RedirectResponse
    {
        if (! $request->user()->hasPermissionTo('billing.manage')) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $this->subscriptions->changePlan($request->user()->currentTeam, $plan, $validated['billing_cycle']);

        return back()->with('success', "Plan changed to {$plan->name}.");
    }
}
