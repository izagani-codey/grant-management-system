<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\VotCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private string $sig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ────────────────────────────────────────────────────────────
    // Enum helpers
    // ────────────────────────────────────────────────────────────

    public function test_request_status_enum_helpers(): void
    {
        $this->assertTrue(RequestStatus::COMPLETED->isFinal());
        $this->assertTrue(RequestStatus::DECLINED->isFinal());
        $this->assertFalse(RequestStatus::SUBMITTED->isFinal());
        $this->assertFalse(RequestStatus::STAFF1_REVIEWED->isFinal());
        $this->assertFalse(RequestStatus::STAFF2_APPROVED->isFinal());
        $this->assertFalse(RequestStatus::RETURNED->isFinal());

        $this->assertTrue(RequestStatus::SUBMITTED->canBeActionedByStaff1());
        $this->assertTrue(RequestStatus::STAFF2_APPROVED->canBeActionedByStaff1());
        $this->assertFalse(RequestStatus::STAFF1_REVIEWED->canBeActionedByStaff1());

        $this->assertTrue(RequestStatus::STAFF1_REVIEWED->canBeActionedByStaff2());
        $this->assertTrue(RequestStatus::SUBMITTED->canBeActionedByStaff2()); // override
        $this->assertIsBool(RequestStatus::SUBMITTED->canBeActionedByStaff1()); // check return type
    }

    public function test_returned_status_allows_user_resubmit(): void
    {
        $this->assertTrue(RequestStatus::RETURNED->canBeResubmittedByUser());
        $this->assertFalse(RequestStatus::SUBMITTED->canBeResubmittedByUser());
        $this->assertFalse(RequestStatus::DECLINED->canBeResubmittedByUser());
    }

    // ────────────────────────────────────────────────────────────
    // Request creation
    // ────────────────────────────────────────────────────────────

    public function test_admission_user_can_create_request(): void
    {
        $requestType = RequestType::factory()->create(['requires_vot' => true]);
        $votCode = VotCode::factory()->create();
        $admission = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($admission)->post('/requests', [
            'request_type_id' => $requestType->id,
            'description'     => 'Test grant request',
            'vot_items'       => [
                ['vot_code' => $votCode->code, 'description' => 'Item 1', 'amount' => 500],
            ],
            'signature_data'  => $this->sig,
            'document'        => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('requests', [
            'user_id'    => $admission->id,
            'status_id'  => RequestStatus::SUBMITTED->value,
            'total_amount' => 500,
        ]);
    }

    public function test_request_creation_without_vot_when_not_required(): void
    {
        $requestType = RequestType::factory()->create(['requires_vot' => false]);
        $admission = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($admission)->post('/requests', [
            'request_type_id' => $requestType->id,
            'description'     => 'No VOT request',
            'signature_data'  => $this->sig,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('requests', [
            'user_id'   => $admission->id,
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);
    }

    public function test_only_admission_can_create_requests(): void
    {
        $requestType = RequestType::factory()->create();
        $staff1 = User::factory()->create(['role' => 'staff1']);

        $response = $this->actingAs($staff1)->post('/requests', [
            'request_type_id' => $requestType->id,
            'description'     => 'Test',
            'signature_data'  => $this->sig,
        ]);

        $response->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────
    // Staff1 transitions
    // ────────────────────────────────────────────────────────────

    public function test_staff1_can_mark_request_as_reviewed(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->submitted()->create();

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);
    }

    public function test_staff1_can_return_request_with_reason(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->submitted()->create();

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id'     => RequestStatus::RETURNED->value,
            'return_reason' => 'Missing supporting documents',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'            => $request->id,
            'status_id'     => RequestStatus::RETURNED->value,
            'return_reason' => 'Missing supporting documents',
        ]);
    }

    public function test_staff1_return_requires_reason(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->submitted()->create();

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::RETURNED->value,
            // no return_reason
        ]);

        $response->assertSessionHasErrors('return_reason');
    }

    public function test_staff1_can_decline_request_with_reason(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->submitted()->create();

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id'      => RequestStatus::DECLINED->value,
            'decline_reason' => 'Does not meet criteria',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'             => $request->id,
            'status_id'      => RequestStatus::DECLINED->value,
            'decline_reason' => 'Does not meet criteria',
        ]);
    }

    public function test_staff1_can_mark_staff2_approved_as_completed(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->staff2Approved()->create();

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::COMPLETED->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::COMPLETED->value,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Staff2 transitions
    // ────────────────────────────────────────────────────────────

    public function test_staff2_can_approve_staff1_reviewed_request(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $request = GrantRequest::factory()->staff1Reviewed()->create();

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id'             => RequestStatus::STAFF2_APPROVED->value,
            'staff2_signature_data' => $this->sig,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);
    }

    public function test_staff2_can_override_submitted_to_approved(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $request = GrantRequest::factory()->submitted()->create();

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id'             => RequestStatus::STAFF2_APPROVED->value,
            'staff2_signature_data' => $this->sig,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);
    }

    public function test_staff2_can_return_request(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $request = GrantRequest::factory()->staff1Reviewed()->create();

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id'     => RequestStatus::RETURNED->value,
            'return_reason' => 'Signature not valid',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::RETURNED->value,
        ]);
    }

    public function test_staff2_approval_requires_signature(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);
        $request = GrantRequest::factory()->staff1Reviewed()->create();

        $response = $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
            // no signature
        ]);

        $response->assertSessionHasErrors('staff2_signature_data');
    }

    // ────────────────────────────────────────────────────────────
    // User resubmit after RETURNED
    // ────────────────────────────────────────────────────────────

    public function test_user_can_edit_returned_request(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $requestType = RequestType::factory()->create(['requires_vot' => false]);
        $request = GrantRequest::factory()->returned()->create([
            'user_id'         => $admission->id,
            'request_type_id' => $requestType->id,
        ]);

        $response = $this->actingAs($admission)->patch("/requests/{$request->id}", [
            'request_type_id' => $requestType->id,
            'description'     => 'Updated description after revision',
            'signature_data'  => $this->sig,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::SUBMITTED->value,
        ]);
    }

    public function test_user_cannot_edit_submitted_request(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $requestType = RequestType::factory()->create(['requires_vot' => false]);
        $request = GrantRequest::factory()->submitted()->create([
            'user_id'         => $admission->id,
            'request_type_id' => $requestType->id,
        ]);

        $response = $this->actingAs($admission)->patch("/requests/{$request->id}", [
            'request_type_id' => $requestType->id,
            'description'     => 'Should not work',
            'signature_data'  => $this->sig,
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_edit_declined_request(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $requestType = RequestType::factory()->create(['requires_vot' => false]);
        $request = GrantRequest::factory()->declined()->create([
            'user_id'         => $admission->id,
            'request_type_id' => $requestType->id,
        ]);

        $response = $this->actingAs($admission)->patch("/requests/{$request->id}", [
            'request_type_id' => $requestType->id,
            'description'     => 'Should not work',
            'signature_data'  => $this->sig,
        ]);

        $response->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────
    // Invalid transitions
    // ────────────────────────────────────────────────────────────

    public function test_staff1_cannot_approve_staff1_reviewed_request(): void
    {
        $staff1 = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->staff1Reviewed()->create();

        $response = $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF2_APPROVED->value,
        ]);

        // Should not succeed — staff1 can't do staff2 approval
        $this->assertDatabaseHas('requests', [
            'id'        => $request->id,
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);
    }

    public function test_admission_cannot_change_status(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $request = GrantRequest::factory()->submitted()->create([
            'user_id' => $admission->id,
        ]);

        $response = $this->actingAs($admission)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);

        $response->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────
    // Complete workflow end-to-end
    // ────────────────────────────────────────────────────────────

    public function test_complete_workflow_submission_to_completion(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1    = User::factory()->create(['role' => 'staff1']);
        $staff2    = User::factory()->create(['role' => 'staff2']);
        $requestType = RequestType::factory()->create(['requires_vot' => false]);

        // 1. User submits
        $this->actingAs($admission)->post('/requests', [
            'request_type_id' => $requestType->id,
            'description'     => 'Full workflow test',
            'signature_data'  => $this->sig,
        ]);

        $request = GrantRequest::latest()->first();
        $this->assertEquals(RequestStatus::SUBMITTED->value, $request->status_id);

        // 2. Staff1 reviews
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);
        $this->assertEquals(RequestStatus::STAFF1_REVIEWED->value, $request->fresh()->status_id);

        // 3. Staff2 approves with signature
        $this->actingAs($staff2)->patch("/requests/{$request->id}/status", [
            'status_id'             => RequestStatus::STAFF2_APPROVED->value,
            'staff2_signature_data' => $this->sig,
        ]);
        $this->assertEquals(RequestStatus::STAFF2_APPROVED->value, $request->fresh()->status_id);

        // 4. Staff1 marks complete
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::COMPLETED->value,
        ]);
        $this->assertEquals(RequestStatus::COMPLETED->value, $request->fresh()->status_id);
        $this->assertTrue($request->fresh()->getStatus()->isFinal());
    }

    public function test_return_and_resubmit_workflow(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $staff1    = User::factory()->create(['role' => 'staff1']);
        $requestType = RequestType::factory()->create(['requires_vot' => false]);

        // 1. User submits
        $this->actingAs($admission)->post('/requests', [
            'request_type_id' => $requestType->id,
            'description'     => 'Return test',
            'signature_data'  => $this->sig,
        ]);

        $request = GrantRequest::latest()->first();

        // 2. Staff1 returns it
        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id'     => RequestStatus::RETURNED->value,
            'return_reason' => 'Need more details',
        ]);
        $this->assertEquals(RequestStatus::RETURNED->value, $request->fresh()->status_id);

        // 3. User resubmits
        $this->actingAs($admission)->patch("/requests/{$request->id}", [
            'request_type_id' => $requestType->id,
            'description'     => 'Updated with more details',
            'signature_data'  => $this->sig,
        ]);
        $fresh = $request->fresh();
        $this->assertEquals(RequestStatus::SUBMITTED->value, $fresh->status_id);
        $this->assertNull($fresh->return_reason);
        $this->assertEquals(1, $fresh->revision_count);
    }

    // ────────────────────────────────────────────────────────────
    // Audit logs
    // ────────────────────────────────────────────────────────────

    public function test_audit_log_created_on_status_change(): void
    {
        $staff1  = User::factory()->create(['role' => 'staff1']);
        $request = GrantRequest::factory()->submitted()->create();

        $this->actingAs($staff1)->patch("/requests/{$request->id}/status", [
            'status_id' => RequestStatus::STAFF1_REVIEWED->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'request_id' => $request->id,
            'actor_id'   => $staff1->id,
        ]);
    }
}
