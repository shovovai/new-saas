<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVerification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Queue of pending/failed verifications across all tenants (UIUX §7), with
 * method, attempts, and last error visible for support triage.
 */
class VerificationRequestController extends Controller
{
    public function index(): Response
    {
        $verifications = WebsiteVerification::query()
            ->whereIn('status', ['pending', 'failed'])
            ->with(['website.team'])
            ->latest()
            ->paginate(25);

        return Inertia::render('Admin/VerificationRequests/Index', [
            'verifications' => $verifications,
        ]);
    }
}
