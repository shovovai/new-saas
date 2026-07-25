<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Teams\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function show(Request $request, TeamInvitation $invitation, string $token): Response|RedirectResponse
    {
        if ($invitation->token !== $token) {
            abort(404);
        }

        $invitation->load('team', 'invitedBy');

        return Inertia::render('Invitations/Show', [
            'invitation' => [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'team_name' => $invitation->team->name,
                'invited_by' => $invitation->invitedBy->name,
                'role' => $invitation->role,
                'email' => $invitation->email,
                'is_pending' => $invitation->isPending(),
            ],
            'accountExists' => User::where('email', $invitation->email)->exists(),
        ]);
    }

    public function accept(Request $request, TeamInvitation $invitation, string $token): RedirectResponse
    {
        if ($invitation->token !== $token) {
            abort(404);
        }

        if (! $request->user()) {
            return redirect()->route('login')->with('error', 'Please log in with the invited email address to accept this invitation.');
        }

        $this->invitations->accept($invitation, $request->user());

        return redirect()->route('dashboard')->with('success', "You've joined {$invitation->team->name}.");
    }
}
