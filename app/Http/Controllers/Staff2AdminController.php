<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\FormTemplate;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Staff2AdminController extends BaseController
{
    private const MANAGEABLE_ROLES = ['admission', 'staff1', 'staff2', 'dean', 'admin'];

    public function index()
    {
        $this->ensureAdminAccess();

        // System Stats
        $totalRequests = GrantRequest::count();
        $submitted = GrantRequest::where('status_id', RequestStatus::SUBMITTED->value)->count();
        $staff1Approved = GrantRequest::where('status_id', RequestStatus::STAFF1_APPROVED->value)->count();
        $deanApproved = GrantRequest::where('status_id', RequestStatus::DEAN_APPROVED->value)->count();
        $rejected = GrantRequest::where('status_id', RequestStatus::REJECTED->value)->count();

        // Request Types Stats
        $byType = RequestType::query()
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->take(6)
            ->get();

        // Recent High Priority Requests
        $recentHighPriority = GrantRequest::query()
            ->with('user', 'requestType')
            ->where('is_priority', true)
            ->latest()
            ->take(8)
            ->get();

        // User Stats
        $totalUsers = User::count();
        $admissionUsers = User::where('role', 'admission')->count();
        $staff1Users = User::where('role', 'staff1')->count();
        $staff2Users = User::where('role', 'staff2')->count();
        $deanUsers = User::where('role', 'dean')->count();

        // Form Templates
        $totalTemplates = FormTemplate::count();
        $recentTemplates = FormTemplate::with('uploader')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalRequests',
            'submitted',
            'staff1Approved',
            'deanApproved',
            'rejected',
            'byType',
            'recentHighPriority',
            'totalUsers',
            'admissionUsers',
            'staff1Users',
            'staff2Users',
            'deanUsers',
            'totalTemplates',
            'recentTemplates'
        ));
    }

    public function users()
    {
        $this->ensureAdminAccess();

        $filters = request()->only(['search', 'role']);

        $users = User::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('staff_id', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function ($query, $role) {
                $query->where('role', $role);
            })
            ->orderByRaw("case role
                when 'admin' then 1
                when 'dean' then 2
                when 'staff2' then 3
                when 'staff1' then 4
                when 'admission' then 5
                else 6
            end")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roleCounts = User::query()
            ->selectRaw('role, count(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $roleOptions = self::MANAGEABLE_ROLES;

        return view('admin.users', compact('users', 'filters', 'roleCounts', 'roleOptions'));
    }

    public function storeUser(Request $request)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'staff_id' => ['nullable', 'string', 'max:255', 'unique:users,staff_id'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'employee_level' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'staff_id' => $validated['staff_id'] ?: null,
            'designation' => $validated['designation'] ?: null,
            'department' => $validated['department'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'employee_level' => $validated['employee_level'] ?: null,
        ]);

        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()->role,
            'action' => 'user_created',
            'note' => "Created user {$user->email} with role {$user->role}",
        ]);

        return redirect()
            ->route('admin.users')
            ->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, User $user)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'staff_id' => ['nullable', 'string', 'max:255', Rule::unique('users', 'staff_id')->ignore($user->id)],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'employee_level' => ['nullable', 'string', 'max:255'],
        ]);

        $this->guardAdminRoleChange($user, $validated['role']);

        $originalRole = $user->role;

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'staff_id' => $validated['staff_id'] ?: null,
            'designation' => $validated['designation'] ?: null,
            'department' => $validated['department'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'employee_level' => $validated['employee_level'] ?: null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()->role,
            'action' => 'user_updated',
            'note' => "Updated user {$user->email} from role {$originalRole} to {$user->role}",
        ]);

        return redirect()
            ->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

    public function requestTypes()
    {
        $this->ensureAdminAccess();

        $requestTypes = RequestType::query()
            ->withCount('requests')
            ->with('defaultTemplate')
            ->latest('created_at')
            ->paginate(20);

        $formTemplates = FormTemplate::where('is_active', true)->get();

        return view('staff2.admin-request-types', compact('requestTypes', 'formTemplates'));
    }

    public function storeRequestType()
    {
        try {
            $validated = request()->validate([
                'name' => 'required|string|max:255|unique:request_types',
                'description' => 'nullable|string',
            ]);

            // Create slug from name
            $validated['slug'] = \Str::slug($validated['name']);

            $requestType = RequestType::create($validated);

            // Log the action
            AuditLog::create([
                'actor_id' => auth()->id(),
                'actor_role' => auth()->user()->role,
                'action' => 'request_type_created',
                'request_type_id' => $requestType->id,
                'note' => 'Created request type: ' . $requestType->name,
            ]);

            return back()->with('success', 'Request type created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error creating request type: ' . $e->getMessage());
            return back()->with('error', 'Unable to create request type at the moment. Please try again.')->withInput();
        }
    }

    public function updateRequestType($id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
            
            $validated = request()->validate([
                'name' => 'required|string|max:255|unique:request_types,name,' . $id,
                'description' => 'nullable|string',
                'default_template_id' => 'nullable|exists:form_templates,id',
            ]);

            // Update slug if name changed
            $validated['slug'] = \Str::slug($validated['name']);

            $requestType->update($validated);

            return back()->with('success', 'Request type updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating request type: ' . $e->getMessage());
            return back()->with('error', 'Unable to update request type at the moment. Please try again.')->withInput();
        }
    }

    public function destroyRequestType($id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
            
            // Check if there are requests using this type
            if ($requestType->requests()->count() > 0) {
                return back()->with('error', 'Cannot delete request type that has associated requests.');
            }

            $requestType->delete();

            return back()->with('success', 'Request type deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting request type: ' . $e->getMessage());
            return back()->with('error', 'Unable to delete request type at the moment. Please try again.');
        }
    }

    public function deploymentPlaybook()
    {
        $this->ensureAdminAccess();

        return view('staff2.deployment-playbook');
    }

    private function ensureAdminAccess(): void
    {
        if (!auth()->user()?->canAccessAdminPanel()) {
            abort(403, 'Unauthorized access to admin panel');
        }
    }

    private function guardAdminRoleChange(User $user, string $newRole): void
    {
        if ($user->role !== 'admin' || $newRole === 'admin') {
            return;
        }

        $adminCount = User::where('role', 'admin')->count();

        if ($adminCount <= 1) {
            abort(422, 'At least one admin account must remain in the system.');
        }
    }
}
