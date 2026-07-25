<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ApiKeys/Index', [
            'keys' => ApiKey::query()->with('team:id,name')->latest()->paginate(25),
        ]);
    }

    public function revoke(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['revoked_at' => now()]);

        return back()->with('success', 'API key revoked.');
    }
}
