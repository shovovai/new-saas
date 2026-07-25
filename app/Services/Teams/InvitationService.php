<?php

namespace App\Services\Teams;

use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Real email-based team invitations — works for an email with no
 * SiteGuardian AI account yet, unlike inviting only-existing-users.
 * Acceptance is a signed URL (Illuminate\Support\Facades\URL), not a
 * bespoke token comparison.
 */
class InvitationService
{
    public function invite(Team $team, User $inviter, string $email, string $role): TeamInvitation
    {
        if ($team->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This user is already on the team.']);
        }

        $existing = $team->invitations()->where('email', $email)->whereNull('accepted_at')->first();
        $existing?->delete();

        $invitation = $team->invitations()->create([
            'invited_by_user_id' => $inviter->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new TeamInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Attaches the given user to the invitation's team. If the user has
     * no team yet (fresh registration), makes this their current team.
     */
    public function accept(TeamInvitation $invitation, User $user): void
    {
        if (! $invitation->isPending()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is no longer valid.']);
        }

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            throw ValidationException::withMessages(['invitation' => 'This invitation was sent to a different email address.']);
        }

        if (! $invitation->team->members()->where('user_id', $user->id)->exists()) {
            $invitation->team->members()->attach($user->id, ['role' => $invitation->role]);
        }

        if (! $user->current_team_id) {
            $user->forceFill(['current_team_id' => $invitation->team_id])->save();
        }

        $invitation->update(['accepted_at' => now()]);
    }
}
