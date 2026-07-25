<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Teams\InvitationService;
use App\Services\Teams\TeamService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view. When arriving from an invitation
     * link, pre-fills (and locks) the invited email address.
     */
    public function create(Request $request): Response
    {
        $invitation = $this->pendingInvitation($request->query('invitation'), $request->query('token'));

        return Inertia::render('Auth/Register', [
            'invitation' => $invitation ? [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'email' => $invitation->email,
                'team_name' => $invitation->team->name,
            ] : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, TeamService $teams, InvitationService $invitations): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $invitation = $this->pendingInvitation($request->input('invitation_id'), $request->input('invitation_token'));

        if ($invitation && strcasecmp($invitation->email, $user->email) === 0) {
            $invitations->accept($invitation, $user);
        } else {
            $teams->createPersonalTeam($user);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function pendingInvitation(?string $id, ?string $token): ?TeamInvitation
    {
        if (! $id || ! $token) {
            return null;
        }

        $invitation = TeamInvitation::with('team')->find($id);

        return $invitation && $invitation->token === $token && $invitation->isPending() ? $invitation : null;
    }
}
