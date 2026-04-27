<?php

namespace Tests\Feature;

use App\Models\Request as GrantRequest;
use App\Models\User;
use App\Models\Notification;
use App\Models\RequestType;
use App\Models\VotCode;
use App\Enums\RequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Notification Creation Tests
    public function test_workflow_notification_is_created_on_status_change(): void
    {
        $requestType = RequestType::factory()->create();
        $votCode = VotCode::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);

        // Create request
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'request_type_id' => $requestType->id,
            'status_id' => RequestStatus::SUBMITTED->value,
            'vot_items' => [['vot_code' => $votCode->code, 'description' => 'Test item', 'amount' => 1000.00]],
            'total_amount' => 1000.00,
            'signature_data' => 'data:image/png;base64,AAAA',
            'submitted_at' => now(),
        ]);

        // Staff 1 approves request
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
            'notes' => 'Verified and approved',
        ]);

        // Check if notification was created for admission user
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $admission->id,
            'type' => 'App\Notifications\RequestStatusNotification',
        ]);
    }

    public function test_notification_contains_correct_data(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        // Staff 1 approves request
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
            'notes' => 'Verified and approved',
        ]);

        $notification = Notification::where('notifiable_id', $admission->id)->first();
        
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Request', $notification->data['title']);
        $this->assertStringContainsString('Staff 1 Approved', $notification->data['message']);
    }

    // Notification Display Tests
    public function test_user_can_view_their_notifications(): void
    {
        $user = User::factory()->create();
        
        // Create notifications for the user
        Notification::factory()->count(3)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertOk();
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');
        $this->assertEquals(3, $response->viewData('notifications')->count());
    }

    public function test_user_only_sees_their_own_notifications(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create notifications for both users
        Notification::factory()->count(2)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user1->id,
        ]);

        Notification::factory()->count(3)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->get('/notifications');

        $response->assertOk();
        $response->assertViewHas('notifications');
        $this->assertEquals(2, $response->viewData('notifications')->count());
    }

    // Notification Read/Unread Tests
    public function test_notification_starts_as_unread(): void
    {
        $user = User::factory()->create();
        
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        $this->assertNull($notification->read_at);
        $this->assertTrue($notification->unread());
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->patch("/notifications/{$notification->id}/read");

        $response->assertRedirect('/notifications');
        
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
        $this->assertFalse($notification->unread());
    }

    public function test_user_can_mark_notification_as_unread(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch("/notifications/{$notification->id}/unread");

        $response->assertRedirect('/notifications');
        
        $notification->refresh();
        $this->assertNull($notification->read_at);
        $this->assertTrue($notification->unread());
    }

    // Bulk Notification Operations Tests
    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        
        // Create unread notifications
        Notification::factory()->count(3)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->patch('/notifications/read-all');

        $response->assertRedirect('/notifications');
        
        // All notifications should now be read
        $unreadCount = Notification::where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_user_can_delete_notifications(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/notifications/{$notification->id}");

        $response->assertRedirect('/notifications');
        
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    // Notification Badge Tests
    public function test_notification_badge_shows_unread_count(): void
    {
        $user = User::factory()->create();
        
        // Create unread notifications
        Notification::factory()->count(3)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('3'); // Badge should show count 3
    }

    public function test_notification_badge_updates_when_marked_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        // Initially should show badge
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertSee('1');

        // Mark as read
        $this->actingAs($user)->patch("/notifications/{$notification->id}/read");

        // Badge should be gone
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertDontSee('1'); // Badge should not show count
    }

    // Workflow-Specific Notification Tests
    public function test_admission_notified_when_request_returned(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);

        // Staff 1 returns request
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::RETURNED->value,
            'notes' => 'Needs more information',
        ]);

        // Check if notification was created for admission user
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $admission->id,
            'type' => 'App\Notifications\RequestStatusNotification',
        ]);
    }

    public function test_admission_notified_when_request_declined(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        // Staff 1 declines request
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::DECLINED->value,
            'notes' => 'Does not meet requirements',
        ]);

        // Check if notification was created for admission user
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $admission->id,
            'type' => 'App\Notifications\RequestStatusNotification',
        ]);
    }

    public function test_admission_notified_when_request_approved(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1 = User::factory()->create(['role' => 'staff1']);
        
        $request = GrantRequest::factory()->create([
            'user_id' => $admission->id,
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);

        // Staff 1 completes request after Staff 2 approval
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::COMPLETED->value,
            'notes' => 'Final completion',
        ]);

        // Check if notification was created for admission user
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $admission->id,
            'type' => 'App\Notifications\RequestStatusNotification',
        ]);
    }

    public function test_staff1_notified_when_staff2_returns_request(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
            'verified_by' => $staff1->id,
        ]);

        // Staff 2 returns request
        $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
            'notes' => 'Needs additional verification',
        ]);

        // Check if notification was created for staff1
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $staff1->id,
            'type' => 'App\Notifications\RequestStatusNotification',
        ]);
    }

    // Override Notification Tests
    public function test_override_notification_is_created(): void
    {
        $staff2 = User::factory()->create([
            'role' => 'staff2',
            'override_enabled' => true,
        ]);
        $request = GrantRequest::factory()->create([
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);

        // Staff 2 overrides
        $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            'notes' => 'Override - Staff 1 unavailable',
            'staff2_signature_data' => 'data:image/png;base64,BBBB',
        ]);

        // Check if override notification was created
        $this->assertDatabaseHas('notifications', [
            'type' => 'App\Notifications\OverrideNotification',
        ]);
    }

    // Notification Filtering Tests
    public function test_notifications_can_be_filtered_by_read_status(): void
    {
        $user = User::factory()->create();
        
        // Create mixed read/unread notifications
        Notification::factory()->count(2)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => null,
        ]);

        Notification::factory()->count(3)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'read_at' => now(),
        ]);

        // Test unread filter
        $response = $this->actingAs($user)->get('/notifications?filter=unread');
        $response->assertOk();
        $this->assertEquals(2, $response->viewData('notifications')->count());

        // Test read filter
        $response = $this->actingAs($user)->get('/notifications?filter=read');
        $response->assertOk();
        $this->assertEquals(3, $response->viewData('notifications')->count());
    }

    // Notification Security Tests
    public function test_user_cannot_access_other_users_notifications(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->get("/notifications/{$notification->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_modify_other_users_notifications(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user2->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user1)->patch("/notifications/{$notification->id}/read");

        $response->assertForbidden();
        
        $notification->refresh();
        $this->assertNull($notification->read_at);
    }

    // Notification Performance Tests
    public function test_notification_index_loads_efficiently(): void
    {
        $user = User::factory()->create();
        
        // Create many notifications
        Notification::factory()->count(100)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/notifications');

        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        $response->assertOk();
        
        // Should load in under 1 second even with 100 notifications
        $this->assertLessThan(1.0, $loadTime);
    }

    // Notification Cleanup Tests
    public function test_old_notifications_can_be_deleted(): void
    {
        $user = User::factory()->create();
        
        // Create old notifications
        Notification::factory()->count(5)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'created_at' => now()->subDays(90),
        ]);

        // Create recent notifications
        Notification::factory()->count(3)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);

        // Test cleanup functionality (if implemented)
        $response = $this->actingAs($user)->delete('/notifications/cleanup');

        // This would depend on your implementation
        // For now, just test the endpoint exists
        $response->assertRedirect('/notifications');
    }

    // Notification Pagination Tests
    public function test_notifications_are_paginated(): void
    {
        $user = User::factory()->create();
        
        // Create more notifications than default page size
        Notification::factory()->count(25)->create([
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertOk();
        $response->assertViewHas('notifications');
        
        // Should have pagination links
        $response->assertSee('Next');
        $response->assertSee('Previous');
    }
}
