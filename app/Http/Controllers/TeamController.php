<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Teams\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function show(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Team/Show', [
            'members' => $team->members()->get(['users.id', 'users.name', 'users.email']),
            'pendingInvitations' => $team->invitations()->whereNull('accepted_at')->latest()->get(['id', 'email', 'role', 'created_at', 'expires_at']),
            'roles' => collect(TeamRole::cases())->map(fn (TeamRole $r) => ['value' => $r->value, 'label' => $r->label()]),
            'canManage' => $request->user()->hasPermissionTo('team.manage'),
            'canInvite' => $request->user()->hasPermissionTo('team.invite'),
        ]);
    }

    /**
     * Sends a real email invitation — works for any address, whether or
     * not it already has a SiteGuardian AI account.
     */
    public function invite(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        if (! $request->user()->hasPermissionTo('team.invite')) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:'.implode(',', TeamRole::values())],
        ]);

        $this->invitations->invite($team, $request->user(), $validated['email'], $validated['role']);

        return back()->with('success', "Invitation sent to {$validated['email']}.");
    }

    public function revokeInvitation(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        if (! $request->user()->hasPermissionTo('team.invite') || $invitation->team_id !== $request->user()->current_team_id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        if (! $request->user()->hasPermissionTo('team.manage')) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:'.implode(',', TeamRole::values())],
        ]);

        $team->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Role updated.');
    }

    public function removeMember(Request $request, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        if (! $request->user()->hasPermissionTo('team.remove_member')) {
            abort(403);
        }

        if ($team->owner_id === $user->id) {
            throw ValidationException::withMessages(['user' => 'The team owner cannot be removed.']);
        }

        $team->members()->detach($user->id);

        return back()->with('success', 'Member removed from the team.');
    }
}
