<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Request as GrantRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ======================================================
    // 🔹 PROFILE TESTS
    // ======================================================

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create([
            'staff_id' => 'STAFF001',
            'designation' => 'Lecturer',
            'department' => 'Engineering',
            'phone' => '+60123456789',
            'employee_level' => 'Academic',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.edu',
            'staff_id' => 'STAFF002',
            'designation' => 'Senior Lecturer',
            'department' => 'Science',
            'phone' => '+60198765432',
            'employee_level' => 'Senior Academic',
        ])
        ->assertRedirect('/profile/edit')
        ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'updated@example.edu',
        ]);
    }

    public function test_user_cannot_use_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);

        $this->actingAs($user1)->patch('/profile', [
            'name' => $user1->name,
            'email' => 'user2@test.com',
        ])
        ->assertSessionHasErrors('email');
    }

    public function test_user_cannot_update_with_invalid_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Test',
            'email' => 'invalid-email',
        ])
        ->assertSessionHasErrors('email');
    }

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ])
        ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    public function test_user_cannot_delete_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)->delete('/profile', [
            'password' => 'wrong',
        ])
        ->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($user);
    }

    // ======================================================
    // 🔹 SIGNATURE TESTS
    // ======================================================

    public function test_user_can_save_signature(): void
    {
        $user = User::factory()->create();

        $signature = 'data:image/png;base64,VALIDBASE64';

        $this->actingAs($user)->post('/profile/signature', [
            'signature_data' => $signature,
        ])
        ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'signature_data' => $signature,
        ]);
    }

    public function test_signature_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/signature', [])
            ->assertSessionHasErrors('signature_data');
    }

    public function test_invalid_signature_format_fails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->json('POST', '/profile/signature', [
            'signature_data' => 'invalid-data',
        ])
        ->assertJsonValidationErrors('signature_data');
    }

    // ======================================================
    // 🔹 ROLE & PERMISSION TESTS
    // ======================================================

    public function test_admission_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admission']);

        $this->actingAs($user)->get('/profile')->assertOk();
        $this->actingAs($user)->get('/requests/create')->assertOk();
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_staff1_permissions(): void
    {
        $user = User::factory()->create(['role' => 'staff1']);

        $this->actingAs($user)->get('/requests/create')->assertForbidden();
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_staff2_permissions(): void
    {
        $user = User::factory()->create(['role' => 'staff2']);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/requests/create')->assertForbidden();
    }

    public function test_admin_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/requests/create')->assertForbidden();
    }

    // ======================================================
    // 🔹 SEARCH & FILTER TESTS
    // ======================================================

    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $john = User::factory()->create(['name' => 'John Doe']);
        $jane = User::factory()->create(['name' => 'Jane Smith']);

        $this->actingAs($admin)
            ->get('/admin/users?search=John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_admin_can_filter_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $staff1 = User::factory()->create(['role' => 'staff1']);
        $staff2 = User::factory()->create(['role' => 'staff2']);

        $this->actingAs($admin)
            ->get('/admin/users?role=staff1')
            ->assertSee($staff1->name)
            ->assertDontSee($staff2->name);
    }

    // ======================================================
    // 🔹 RELATIONSHIP TESTS
    // ======================================================

    public function test_user_has_many_requests(): void
    {
        $user = User::factory()->create();

        $requests = GrantRequest::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $user->requests);

        $this->assertEqualsCanonicalizing(
            $requests->pluck('id')->toArray(),
            $user->requests->pluck('id')->toArray()
        );
    }

    // ======================================================
    // 🔹 BASIC STATS TEST
    // ======================================================

    public function test_user_role_counts_are_correct(): void
    {
        User::factory()->create(['role' => 'admission']);
        User::factory()->create(['role' => 'staff1']);
        User::factory()->create(['role' => 'staff2']);
        User::factory()->create(['role' => 'admin']);

        $this->assertEquals(4, User::count());
        $this->assertEquals(1, User::where('role', 'admin')->count());
    }
}