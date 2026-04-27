<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\OverrideLog;
use App\Models\VotCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverrideSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Override Mode Toggle Tests
    public function test_staff2_can_enable_override_mode(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $response = $this->actingAs($staff2)->post('/requests/toggle-override-mode');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staff2->refresh();
        $this->assertTrue($staff2->override_enabled);
        $this->assertNotNull($staff2->override_enabled_at);
    }

    public function test_staff2_can_disable_override_mode(): void
    {
        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);

        $response = $this->actingAs($staff2)->post('/requests/toggle-override-mode');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staff2->refresh();
        $this->assertFalse($staff2->override_enabled);
        $this->assertNull($staff2->override_enabled_at);
    }

    public function test_only_staff2_can_toggle_override_mode(): void
    {
        $roles = ['admission', 'staff1', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->post('/requests/toggle-override-mode');

            $response->assertForbidden();
        }
    }

    public function test_guest_cannot_toggle_override_mode(): void
    {
        $response = $this->post('/requests/toggle-override-mode');

        $response->assertRedirect('/login');
    }

    // Override Helper Methods Tests
    public function test_user_override_helper_methods(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $staff1 = User::factory()->create(['role' => 'staff1']);

        // Test canOverrideRequests()
        $this->assertTrue($staff2->canOverrideRequests());
        $this->assertFalse($staff1->canOverrideRequests());

        // Test enableOverride()
        $this->assertFalse($staff2->override_enabled);
        $staff2->enableOverride();
        $this->assertTrue($staff2->override_enabled);
        $this->assertNotNull($staff2->override_enabled_at);

        // Test disableOverride()
        $staff2->disableOverride();
        $this->assertFalse($staff2->override_enabled);
        $this->assertNull($staff2->override_enabled_at);

        // Test toggleOverride()
        $staff2->toggleOverride();
        $this->assertTrue($staff2->override_enabled);
        $staff2->toggleOverride();
        $this->assertFalse($staff2->override_enabled);
    }

    // Override Workflow Tests
    public function test_staff2_can_override_staff1_stage(): void
    {
        $requestType = RequestType::factory()->create();
        $votCode = VotCode::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);
        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);

        // Create request in SUBMITTED status
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'vot_items' => [['vot_code' => $votCode->code, 'description' => 'Test item', 'amount' => 1000.00]],
            'total_amount' => 1000.00,
            'signature_data' => 'data:image/png;base64,AAAA',
            'submitted_at' => now(),
        ]);

        // Staff 2 overrides directly to STAFF2_APPROVED
        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override - Staff 1 unavailable',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
        $this->assertTrue($request->is_override);
        $this->assertEquals($staff2->id, $request->recommended_by);
        $this->assertEquals($staff2->id, $request->verified_by); // Should be set during override
    }

    public function test_staff2_cannot_override_without_override_mode(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => false, // Override mode disabled
        ]);

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override attempt',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        $response->assertForbidden();
        $request->refresh();
        $this->assertEquals(RequestStatus::SUBMITTED->value, $request->status_id);
        $this->assertFalse($request->is_override);
    }

    public function test_staff2_can_override_with_reason(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);

        $overrideReason = 'Emergency request - Staff 1 on leave';

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => $overrideReason,
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        $response->assertRedirect("/requests/{$request->id}");
        $request->refresh();
        
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
        $this->assertTrue($request->is_override);
        
        // Check if override reason is stored in comments or audit logs
        $this->assertDatabaseHas('audit_logs', [
            'request_id' => $request->id,
            'user_id' => $staff2->id,
            'action' => 'override_applied',
        ]);
    }

    // Override Logging Tests
    public function test_override_actions_are_logged(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);

        $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override test',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        $this->assertDatabaseHas('override_logs', [
            'request_id' => $request->id,
            'user_id' => $staff2->id,
            'action' => 'staff1_override',
            'old_status' => RequestStatus::SUBMITTED->value,
            'new_status' => RequestStatus::STAFF2_APPROVED->value,
        ]);
    }

    public function test_override_mode_changes_are_logged(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);

        // Enable override mode
        $this->actingAs($staff2)->post('/requests/toggle-override-mode');

        $this->assertDatabaseHas('override_logs', [
            'user_id' => $staff2->id,
            'action' => 'override_enabled',
        ]);

        // Disable override mode
        $this->actingAs($staff2)->post('/requests/toggle-override-mode');

        $this->assertDatabaseHas('override_logs', [
            'user_id' => $staff2->id,
            'action' => 'override_disabled',
        ]);
    }

    // Override UI Tests
    public function test_override_mode_banner_display(): void
    {
        $staff2WithOverride = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);

        $staff2WithoutOverride = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => false,
        ]);

        // Test staff2 with override sees active banner
        $response = $this->actingAs($staff2WithOverride)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Override Mode Active');
        $response->assertSee('Disable Override Mode');

        // Test staff2 without override sees available banner
        $response = $this->actingAs($staff2WithoutOverride)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Override Mode Available');
        $response->assertSee('Enable Override Mode');
    }

    public function test_other_roles_do_not_see_override_ui(): void
    {
        $roles = ['admission', 'staff1', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get('/dashboard');

            $response->assertOk();
            $response->assertDontSee('Override Mode');
            $response->assertDontSee('Enable Override Mode');
            $response->assertDontSee('Disable Override Mode');
        }
    }

    // Override Permission Tests
    public function test_staff2_override_permissions(): void
    {
        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);

        // Test canAccessAdminPanel() method
        $this->assertTrue($staff2->canAccessAdminPanel());

        // Test override-specific permissions
        $this->assertTrue($staff2->canOverrideRequests());
        $this->assertTrue($staff2->override_enabled);
    }

    public function test_non_staff2_users_cannot_override(): void
    {
        $roles = ['admission', 'staff1', 'admin'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertFalse($user->canOverrideRequests());
            $this->assertFalse($user->override_enabled);
        }
    }

    // Override Statistics Tests
    public function test_override_statistics(): void
    {
        // Create users with different override states
        $staff2WithOverride = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
        ]);
        
        $staff2WithoutOverride = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => false,
        ]);

        // Test override count
        $this->assertEquals(1, User::where('override_enabled', true)->count());
        $this->assertEquals(1, User::where('override_enabled', false)->where('role', 'staff2')->count());
    }

    // Override Security Tests
    public function test_override_mode_requires_proper_authentication(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);

        // Test without authentication
        $response = $this->post('/requests/toggle-override-mode');
        $response->assertRedirect('/login');

        // Test with different user
        $otherUser = User::factory()->create(['role' => 'staff1']);
        $response = $this->actingAs($otherUser)->post('/requests/toggle-override-mode');
        $response->assertForbidden();
    }

    // Override Time Tracking Tests
    public function test_override_timestamps_are_recorded(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $beforeTime = now()->subSecond();
        
        // Enable override mode
        $this->actingAs($staff2)->post('/requests/toggle-override-mode');
        
        $staff2->refresh();
        $this->assertNotNull($staff2->override_enabled_at);
        $this->assertGreaterThan($beforeTime, $staff2->override_enabled_at);

        // Disable override mode
        $this->actingAs($staff2)->post('/requests/toggle-override-mode');
        
        $staff2->refresh();
        $this->assertNull($staff2->override_enabled_at);
    }

    // Override Request Status Tests
    public function test_override_requests_have_correct_status(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
            'is_override' => false,
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
        ]);

        $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override test',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        $request->refresh();
        $this->assertTrue($request->is_override);
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
    }

    // Override Limitation Tests
    public function test_override_cannot_bypass_completion_stage(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
        ]);

        // Staff 2 should not be able to override directly to COMPLETED
        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::COMPLETED->value,
            'notes' => 'Override attempt',
        ]);

        $response->assertForbidden();
        $request->refresh();
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->status_id);
        $this->assertFalse($request->is_override);
    }

    // Override Persistence Tests
    public function test_override_state_persists_across_sessions(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);

        // Enable override mode
        $this->actingAs($staff2)->post('/requests/toggle-override-mode');

        // Simulate new session by refreshing user from database
        $staff2->refresh();
        $this->assertTrue($staff2->override_enabled);
        $this->assertNotNull($staff2->override_enabled_at);
    }

    // Override Notification Tests
    public function test_override_triggers_notifications(): void
    {
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
        ]);

        $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override test',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        // Check if override notification was created
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\User',
            'type' => 'App\Notifications\OverrideNotification',
        ]);
    }
}
