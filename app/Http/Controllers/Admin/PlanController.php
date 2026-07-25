<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plan builder + live feature matrix (UIUX §7): this table IS the
 * plan_features data, edited in place, saved without a deploy — never a
 * hardcoded plan-name check anywhere in the app.
 */
class PlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::query()->orderBy('sort_order')->with('features')->get(),
            'features' => Feature::query()->orderBy('category')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plans,slug'],
            'price_monthly' => ['required', 'integer', 'min:0'],
            'price_yearly' => ['required', 'integer', 'min:0'],
            'max_websites' => ['required', 'integer', 'min:0'],
            'max_team_members' => ['required', 'integer', 'min:0'],
            'max_scans_per_month' => ['required', 'integer', 'min:0'],
            'scan_frequency' => ['required', 'string'],
            'ai_credits' => ['required', 'integer', 'min:0'],
            'storage_mb' => ['required', 'integer', 'min:0'],
        ]);

        Plan::create($validated + ['sort_order' => Plan::query()->max('sort_order') + 1]);

        return back()->with('success', 'Plan created.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price_monthly' => ['sometimes', 'integer', 'min:0'],
            'price_yearly' => ['sometimes', 'integer', 'min:0'],
            'max_websites' => ['sometimes', 'integer', 'min:0'],
            'max_team_members' => ['sometimes', 'integer', 'min:0'],
            'max_scans_per_month' => ['sometimes', 'integer', 'min:0'],
            'scan_frequency' => ['sometimes', 'string'],
            'ai_credits' => ['sometimes', 'integer', 'min:0'],
            'storage_mb' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'stripe_price_id_monthly' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_price_id_yearly' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paddle_price_id_monthly' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paddle_price_id_yearly' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $plan->update($validated);

        return back()->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }

    /**
     * Toggle a single cell in the feature matrix (checkbox grid: features x
     * plans) — the core admin-editable, zero-deploy commercial gate.
     */
    public function toggleFeature(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'feature_id' => ['required', 'exists:features,id'],
            'enabled' => ['required', 'boolean'],
        ]);

        $plan->features()->syncWithoutDetaching([
            $validated['feature_id'] => ['enabled' => $validated['enabled']],
        ]);

        return back()->with('success', 'Feature matrix updated.');
    }
}
