<?php

namespace Tests\Feature;

use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\FormTemplate;
use App\Models\VotCode;
use App\Enums\RequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // Admin Dashboard Access Tests
    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Admin Dashboard');
    }

    public function test_admin_dashboard_shows_correct_statistics(): void
    {
        // Create test data
        GrantRequest::factory()->create(['status_id' => RequestStatus::SUBMITTED->value]);
        GrantRequest::factory()->create(['status_id' => RequestStatus::STAFF1_REVIEWED->value]);
        GrantRequest::factory()->create(['status_id' => RequestStatus::STAFF2_APPROVED->value]);
        GrantRequest::factory()->create(['status_id' => RequestStatus::COMPLETED->value]);
        GrantRequest::factory()->create(['status_id' => RequestStatus::DECLINED->value]);

        User::factory()->create(['role' => 'admission']);
        User::factory()->create(['role' => 'staff1']);
        User::factory()->create(['role' => 'staff2']);

        FormTemplate::factory()->count(3)->create();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalRequests', 5);
        $response->assertViewHas('submitted', 1);
        $response->assertViewHas('staff1Reviewed', 1);
        $response->assertViewHas('staff2Approved', 1);
        $response->assertViewHas('completed', 1);
        $response->assertViewHas('declined', 1);
        $response->assertViewHas('totalUsers', 4); // 3 staff users + admin
        $response->assertViewHas('totalTemplates', 3);
    }

    public function test_non_admin_users_cannot_access_admin_dashboard(): void
    {
        $roles = ['admission', 'staff1', 'staff2'];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get('/admin/dashboard');

            $response->assertForbidden();
        }
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    // Admin User Management Tests
    public function test_admin_can_view_users_list(): void
    {
        // Create users with different roles
        User::factory()->create(['role' => 'admission', 'name' => 'Admission User']);
        User::factory()->create(['role' => 'staff1', 'name' => 'Staff 1 User']);
        User::factory()->create(['role' => 'staff2', 'name' => 'Staff 2 User']);
        User::factory()->create(['role' => 'admin', 'name' => 'Second Admin User']);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertViewIs('staff2.admin-users');
        $response->assertSee('Admission User');
        $response->assertSee('Staff 1 User');
        $response->assertSee('Staff 2 User');
        $response->assertSee('Second Admin User');
    }

    public function test_admin_can_search_users(): void
    {
        User::factory()->create(['name' => 'John Doe', 'role' => 'admission']);
        User::factory()->create(['name' => 'Jane Smith', 'role' => 'staff1']);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/users?search=John');

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        User::factory()->create(['role' => 'staff1', 'name' => 'Staff 1']);
        User::factory()->create(['role' => 'staff2', 'name' => 'Staff 2']);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/users?role=staff1');

        $response->assertOk();
        $response->assertSee('Staff 1');
        $response->assertDontSee('Staff 2');
    }

    // Admin Request Type Management Tests
    public function test_admin_can_view_request_types(): void
    {
        RequestType::factory()->create(['name' => 'Travel Grant', 'description' => 'For travel expenses']);
        RequestType::factory()->create(['name' => 'Research Grant', 'description' => 'For research projects']);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/request-types');

        $response->assertOk();
        $response->assertViewIs('staff2.admin-request-types');
        $response->assertSee('Travel Grant');
        $response->assertSee('Research Grant');
    }

    public function test_admin_can_create_request_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $requestData = [
            'name' => 'New Grant Type',
            'description' => 'Description for new grant type',
        ];

        $response = $this->actingAs($admin)->post('/admin/request-types', $requestData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('request_types', [
            'name' => 'New Grant Type',
            'description' => 'Description for new grant type',
            'slug' => 'new-grant-type',
        ]);
    }

    public function test_admin_can_update_request_type(): void
    {
        $requestType = RequestType::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ];

        $response = $this->actingAs($admin)->put("/admin/request-types/{$requestType->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('request_types', [
            'id' => $requestType->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'slug' => 'updated-name',
        ]);
    }

    public function test_admin_can_delete_request_type(): void
    {
        $requestType = RequestType::factory()->create();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete("/admin/request-types/{$requestType->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('request_types', ['id' => $requestType->id]);
    }

    public function test_admin_cannot_delete_request_type_with_requests(): void
    {
        $requestType = RequestType::factory()->create();
        GrantRequest::factory()->create(['request_type_id' => $requestType->id]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete("/admin/request-types/{$requestType->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('request_types', ['id' => $requestType->id]);
    }

    // Admin Template Management Tests
    public function test_admin_can_upload_template(): void
    {
        $requestType = RequestType::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $templateData = [
            'name' => 'Test Template',
            'description' => 'Test template description',
            'request_type_id' => $requestType->id,
            'file' => UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf'),
        ];

        $response = $this->actingAs($admin)->post('/form-templates', $templateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('form_templates', [
            'name' => 'Test Template',
            'description' => 'Test template description',
            'request_type_id' => $requestType->id,
        ]);
    }

    public function test_admin_can_delete_template(): void
    {
        $template = FormTemplate::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete("/form-templates/{$template->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('form_templates', ['id' => $template->id]);
    }

    // Admin Statistics Tests
    public function test_admin_dashboard_shows_request_statistics(): void
    {
        // Create requests with different statuses
        GrantRequest::factory()->count(5)->create(['status_id' => RequestStatus::SUBMITTED->value]);
        GrantRequest::factory()->count(3)->create(['status_id' => RequestStatus::STAFF1_REVIEWED->value]);
        GrantRequest::factory()->count(2)->create(['status_id' => RequestStatus::STAFF2_APPROVED->value]);
        GrantRequest::factory()->count(1)->create(['status_id' => RequestStatus::COMPLETED->value]);
        GrantRequest::factory()->count(1)->create(['status_id' => RequestStatus::DECLINED->value]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalRequests', 12);
        $response->assertViewHas('submitted', 5);
        $response->assertViewHas('staff1Reviewed', 3);
        $response->assertViewHas('staff2Approved', 2);
        $response->assertViewHas('completed', 1);
        $response->assertViewHas('declined', 1);
    }

    public function test_admin_dashboard_shows_user_statistics(): void
    {
        User::factory()->count(3)->create(['role' => 'admission']);
        User::factory()->count(2)->create(['role' => 'staff1']);
        User::factory()->count(1)->create(['role' => 'staff2']);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalUsers', 7); // 6 staff users + admin
        $response->assertViewHas('admissionUsers', 3);
        $response->assertViewHas('staff1Users', 2);
        $response->assertViewHas('staff2Users', 1);
    }

    public function test_admin_dashboard_shows_template_statistics(): void
    {
        FormTemplate::factory()->count(5)->create();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalTemplates', 5);
    }

    // Admin Navigation Tests
    public function test_admin_navigation_links_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Test admin dashboard link
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        // Test admin users link
        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk();

        // Test admin request types link
        $response = $this->actingAs($admin)->get('/admin/request-types');
        $response->assertOk();

        // Test deployment playbook link
        $response = $this->actingAs($admin)->get('/admin/deployment-playbook');
        $response->assertOk();
    }

    // Admin vs Staff2 Role Separation Tests
    public function test_admin_can_access_staff2_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $staff2Routes = [
            '/staff2/admin-panel',
            '/staff2/admin/users',
            '/staff2/admin/request-types',
        ];

        foreach ($staff2Routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            $response->assertOk();
        }
    }

    public function test_staff2_cannot_access_admin_routes(): void
    {
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $adminRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/request-types',
            '/admin/deployment-playbook',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->actingAs($staff2)->get($route);
            $response->assertForbidden();
        }
    }

    // Admin Quick Actions Tests
    public function test_admin_dashboard_shows_quick_action_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('User Management');
        $response->assertSee('Request Types');
        $response->assertSee('Templates');
        $response->assertSee('Deployment');
    }

    // Admin Recent Activity Tests
    public function test_admin_dashboard_shows_recent_requests(): void
    {
        GrantRequest::factory()->count(4)->create();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('recentRequests');
        $this->assertEquals(4, $response->viewData('recentRequests')->count());
    }

    public function test_admin_dashboard_shows_recent_templates(): void
    {
        FormTemplate::factory()->count(3)->create();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('recentTemplates');
        $this->assertEquals(3, $response->viewData('recentTemplates')->count());
    }

    // Admin Request Type Validation Tests
    public function test_admin_request_type_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Test name is required
        $response = $this->actingAs($admin)->post('/admin/request-types', [
            'description' => 'Description without name',
        ]);

        $response->assertSessionHasErrors('name');

        // Test name must be unique
        RequestType::factory()->create(['name' => 'Existing Type']);

        $response = $this->actingAs($admin)->post('/admin/request-types', [
            'name' => 'Existing Type',
            'description' => 'Duplicate name test',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // Admin Template Validation Tests
    public function test_admin_template_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Test file is required
        $response = $this->actingAs($admin)->post('/form-templates', [
            'name' => 'Test Template',
            'description' => 'Test description',
        ]);

        $response->assertSessionHasErrors('file');

        // Test file must be PDF
        $response = $this->actingAs($admin)->post('/form-templates', [
            'name' => 'Test Template',
            'description' => 'Test description',
            'file' => UploadedFile::fake()->create('template.txt', 100, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('file');
    }

    // Admin Security Tests
    public function test_admin_actions_are_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create a request type
        $this->actingAs($admin)->post('/admin/request-types', [
            'name' => 'Test Type',
            'description' => 'Test description',
        ]);

        // Check if action was logged (assuming audit logging is implemented)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'request_type_created',
        ]);
    }

    // Admin Performance Tests
    public function test_admin_dashboard_loads_efficiently(): void
    {
        // Create a reasonable amount of test data
        GrantRequest::factory()->count(50)->create();
        User::factory()->count(20)->create();
        FormTemplate::factory()->count(10)->create();

        $admin = User::factory()->create(['role' => 'admin']);

        $startTime = microtime(true);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        $response->assertOk();
        
        // Dashboard should load in under 2 seconds
        $this->assertLessThan(2.0, $loadTime);
    }
}
