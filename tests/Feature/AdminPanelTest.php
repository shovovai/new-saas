<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_platform_admin' => true]);
    }

    public function test_non_admin_is_forbidden_from_every_admin_route(): void
    {
        [$user] = $this->createUserWithTeam('professional');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/permissions')->assertForbidden();
        $this->actingAs($user)->get('/admin/coupons')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
    }

    public function test_admin_can_list_and_suspend_and_reinstate_a_user(): void
    {
        $admin = $this->admin();
        [$target] = $this->createUserWithTeam('professional');

        $this->actingAs($admin)->get('/admin/users')->assertOk();

        $this->actingAs($admin)->patch("/admin/users/{$target->id}/toggle-suspension")->assertRedirect();
        $this->assertNotNull($target->fresh()->suspended_at);

        $this->actingAs($admin)->patch("/admin/users/{$target->id}/toggle-suspension")->assertRedirect();
        $this->assertNull($target->fresh()->suspended_at);
    }

    public function test_a_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password'), 'suspended_at' => now()]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_toggle_a_role_permission(): void
    {
        $admin = $this->admin();
        $permission = Permission::query()->first();

        $this->actingAs($admin)->post('/admin/permissions/toggle', [
            'role' => 'viewer',
            'permission_id' => $permission->id,
            'granted' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('role_permissions', ['role' => 'viewer', 'permission_id' => $permission->id]);

        $this->actingAs($admin)->post('/admin/permissions/toggle', [
            'role' => 'viewer',
            'permission_id' => $permission->id,
            'granted' => false,
        ])->assertRedirect();

        $this->assertDatabaseMissing('role_permissions', ['role' => 'viewer', 'permission_id' => $permission->id]);
    }

    public function test_admin_can_create_activate_and_delete_a_coupon(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/coupons', [
            'code' => 'launch20',
            'type' => 'percent',
            'value' => 20,
        ])->assertRedirect();

        $coupon = Coupon::where('code', 'LAUNCH20')->firstOrFail();

        $this->actingAs($admin)->patch("/admin/coupons/{$coupon->id}", ['is_active' => false])->assertRedirect();
        $this->assertFalse($coupon->fresh()->is_active);

        $this->actingAs($admin)->delete("/admin/coupons/{$coupon->id}")->assertRedirect();
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_admin_can_revoke_an_api_key(): void
    {
        $admin = $this->admin();
        [$user, $team] = $this->createUserWithTeam('professional');
        $result = ApiKey::generate($team, $user, 'CI key');

        $this->actingAs($admin)->get('/admin/api-keys')->assertOk();
        $this->actingAs($admin)->patch("/admin/api-keys/{$result['model']->id}/revoke")->assertRedirect();

        $this->assertNotNull($result['model']->fresh()->revoked_at);
    }

    public function test_admin_can_publish_an_announcement_and_it_is_shared_to_authenticated_pages(): void
    {
        $admin = $this->admin();
        [$user] = $this->createUserWithTeam('professional');

        $this->actingAs($admin)->post('/admin/announcements', [
            'title' => 'Scheduled maintenance',
            'body' => 'We will be upgrading infrastructure tonight.',
            'severity' => 'warning',
        ])->assertRedirect();

        $announcement = Announcement::firstOrFail();
        $this->assertTrue($announcement->is_active);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page->where('announcement.title', 'Scheduled maintenance'));
    }

    public function test_maintenance_mode_blocks_non_admins_but_not_admins(): void
    {
        Setting::set('maintenance_mode', '1');

        [$user] = $this->createUserWithTeam('professional');
        $admin = $this->admin();

        $this->actingAs($user)->get('/dashboard')->assertStatus(503);
        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_a_team_member_can_submit_a_support_ticket_and_an_admin_can_reply(): void
    {
        $admin = $this->admin();
        [$user, $team] = $this->createUserWithTeam('professional');

        $this->actingAs($user)->post('/support', [
            'subject' => 'Cannot verify my domain',
            'message' => 'The DNS TXT record check keeps failing.',
        ])->assertRedirect();

        $ticket = SupportTicket::where('team_id', $team->id)->firstOrFail();
        $this->assertSame('open', $ticket->status);

        $this->actingAs($admin)->get("/admin/support-tickets/{$ticket->id}")->assertOk();

        $this->actingAs($admin)->post("/admin/support-tickets/{$ticket->id}/replies", [
            'message' => 'Please double check the record propagated — try again in 10 minutes.',
        ])->assertRedirect();

        $this->assertSame('pending', $ticket->fresh()->status);
        $this->assertSame(2, $ticket->replies()->count());
    }

    public function test_settings_update_persists_and_is_readable_through_the_setting_model(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/admin/settings', [
            'site_name' => 'SiteGuardian AI',
            'support_email' => 'help@siteguardian.ai',
            'maintenance_mode' => false,
            'maintenance_message' => '',
        ])->assertRedirect();

        $this->assertSame('help@siteguardian.ai', Setting::get('support_email'));
    }

    public function test_admin_can_view_the_audit_log(): void
    {
        $admin = $this->admin();
        [$user, $team] = $this->createUserWithTeam('professional');

        AuditLog::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'action' => 'website.verify',
        ]);

        $this->actingAs($admin)->get('/admin/logs')->assertOk();
    }
}
