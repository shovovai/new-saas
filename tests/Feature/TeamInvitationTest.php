<?php

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\Teams\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_inviting_a_new_email_sends_a_real_invitation_mail(): void
    {
        Mail::fake();

        [$owner, $team] = $this->createUserWithTeam('professional');

        $response = $this->actingAs($owner)->post('/team/invitations', [
            'email' => 'newperson@example.com',
            'role' => 'developer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'newperson@example.com',
            'role' => 'developer',
        ]);
        Mail::assertQueued(TeamInvitationMail::class);
    }

    public function test_an_existing_user_can_accept_an_invitation_by_logging_in(): void
    {
        [$owner, $team] = $this->createUserWithTeam('professional');
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $this->actingAs($owner)->post('/team/invitations', ['email' => 'invitee@example.com', 'role' => 'viewer']);
        $invitation = TeamInvitation::where('email', 'invitee@example.com')->firstOrFail();

        $response = $this->actingAs($invitee)->post("/invitations/{$invitation->id}/{$invitation->token}/accept");

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($team->fresh()->members()->where('user_id', $invitee->id)->exists());
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_accepting_with_the_wrong_email_is_rejected(): void
    {
        [$owner, $team] = $this->createUserWithTeam('professional');
        $wrongUser = User::factory()->create(['email' => 'someoneelse@example.com']);

        $this->actingAs($owner)->post('/team/invitations', ['email' => 'invitee@example.com', 'role' => 'viewer']);
        $invitation = TeamInvitation::where('email', 'invitee@example.com')->firstOrFail();

        $this->actingAs($wrongUser)->post("/invitations/{$invitation->id}/{$invitation->token}/accept")
            ->assertSessionHasErrors('invitation');

        $this->assertFalse($team->fresh()->members()->where('user_id', $wrongUser->id)->exists());
    }

    public function test_a_brand_new_user_can_register_through_an_invitation_and_joins_the_team_instead_of_getting_a_personal_team(): void
    {
        Mail::fake();

        [$owner, $team] = $this->createUserWithTeam('professional');

        // Created directly via the service (not an HTTP call) so this
        // request stays a genuine unauthenticated guest — actingAs()
        // persists across requests within a test, and a real browser
        // would never be logged in as the inviter while registering.
        $invitation = app(InvitationService::class)
            ->invite($team, $owner, 'brandnew@example.com', 'seo_expert');

        $response = $this->post('/register', [
            'name' => 'Brand New',
            'email' => 'brandnew@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invitation_id' => $invitation->id,
            'invitation_token' => $invitation->token,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $newUser = User::where('email', 'brandnew@example.com')->firstOrFail();
        $this->assertTrue($team->fresh()->members()->where('user_id', $newUser->id)->exists());
        $this->assertSame($team->id, $newUser->current_team_id);
        $this->assertSame('seo_expert', $team->fresh()->roleFor($newUser));

        // Joined via invitation — must NOT also get a separate personal team.
        $this->assertSame(0, $newUser->ownedTeams()->count());
    }

    public function test_revoking_an_invitation_removes_it(): void
    {
        [$owner, $team] = $this->createUserWithTeam('professional');

        $this->actingAs($owner)->post('/team/invitations', ['email' => 'gone@example.com', 'role' => 'viewer']);
        $invitation = TeamInvitation::where('email', 'gone@example.com')->firstOrFail();

        $this->actingAs($owner)->delete("/team/invitations/{$invitation->id}")->assertRedirect();

        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }
}
