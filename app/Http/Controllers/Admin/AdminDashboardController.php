<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Team;
use App\Models\Website;
use App\Models\WebsiteVerification;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'teams' => Team::query()->count(),
                'websites' => Website::query()->withoutGlobalScopes()->count(),
                'verified_websites' => Website::query()->withoutGlobalScopes()->where('status', 'verified')->count(),
                'pending_verifications' => WebsiteVerification::query()->where('status', 'pending')->count(),
                'plans' => Plan::query()->count(),
            ],
        ]);
    }
}
