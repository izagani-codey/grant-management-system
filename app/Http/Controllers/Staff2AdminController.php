<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\RequestTypeTemplate;
use App\Models\User;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Staff2AdminController extends BaseController
{
    private const MANAGEABLE_ROLES = ['admission', 'staff1', 'staff2', 'admin'];

    public function index()
    {
        $this->ensureAdminAccess();

        // System Stats
        $totalRequests  = GrantRequest::count();
        $submitted      = GrantRequest::where('status_id', RequestStatus::SUBMITTED->value)->count();
        $staff1Approved = GrantRequest::where('status_id', RequestStatus::STAFF1_REVIEWED->value)->count();
        $staff2Approved = GrantRequest::where('status_id', RequestStatus::STAFF2_APPROVED->value)->count();
        $completed      = GrantRequest::where('status_id', RequestStatus::COMPLETED->value)->count();
        $declined       = GrantRequest::where('status_id', RequestStatus::DECLINED->value)->count();

        // Request Types Stats
        $byType = RequestType::query()
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->take(6)
            ->get();

        // Recent Requests
        $recentHighPriority = GrantRequest::query()
            ->with('user', 'requestType')
            ->latest()
            ->take(8)
            ->get();

        // User Stats
        $totalUsers     = User::count();
        $admissionUsers = User::where('role', 'admission')->count();
        $staff1Users    = User::where('role', 'staff1')->count();
        $staff2Users    = User::where('role', 'staff2')->count();

        // Form Templates
        $totalTemplates = Document::where('document_type', 'template')->count();
        $recentTemplates = Document::with('uploader')
            ->where('document_type', 'template')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalRequests',
            'submitted',
            'staff1Approved',
            'staff2Approved',
            'completed',
            'declined',
            'byType',
            'recentHighPriority',
            'totalUsers',
            'admissionUsers',
            'staff1Users',
            'staff2Users',
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
                when 'staff2' then 2
                when 'staff1' then 3
                when 'admission' then 4
                else 5
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
            ->with(['defaultTemplate'])
            ->latest('created_at')
            ->paginate(20);

        $formTemplates = Document::where('document_type', 'template')->get();

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
                'default_template_id' => 'nullable|exists:documents,id',
                'required_documents' => 'nullable|array',
                'required_documents.*' => 'string|max:255',
            ]);

            // Update slug if name changed
            $validated['slug'] = \Str::slug($validated['name']);

            // Filter out empty document entries
            $validated['required_documents'] = array_values(
                array_filter($validated['required_documents'] ?? [], fn ($d) => trim($d) !== '')
            ) ?: null;

            $requestType->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'default_template_id' => $validated['default_template_id'] ?? null,
                'required_documents' => $validated['required_documents'],
            ]);

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

    public function destroyUser(User $user)
    {
        $this->ensureAdminAccess();

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users')->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return redirect()->route('admin.users')->with('error', 'At least one active admin account must remain in the system.');
            }
        }

        $user->update(['is_active' => false]);

        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()->role,
            'action' => 'user_deactivated',
            'note' => "Deactivated user {$user->email} (role: {$user->role})",
        ]);

        return redirect()->route('admin.users')->with('success', "User {$user->name} has been deactivated.");
    }

    public function reactivateUser(User $user)
    {
        $this->ensureAdminAccess();

        $user->update(['is_active' => true]);

        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => auth()->user()->role,
            'action' => 'user_reactivated',
            'note' => "Reactivated user {$user->email} (role: {$user->role})",
        ]);

        return redirect()->route('admin.users')->with('success', "User {$user->name} has been reactivated.");
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

    // ==========================================
    // Checklist Management Methods
    // ==========================================

    public function checklists(Request $request)
    {
        $this->ensureAdminAccess();

        $requestTypes = RequestType::with(['checklistItems' => function($query) {
            $query->orderBy('sort_order');
        }])->get();

        return view('admin.checklists', compact('requestTypes'));
    }

    public function storeChecklistItem(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'request_type_id' => 'required|exists:request_types,id',
            'label' => 'required|string|max:255',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $checklistItem = \App\Models\ChecklistItem::create([
            'request_type_id' => $request->request_type_id,
            'label' => $request->label,
            'is_required' => $request->boolean('is_required', true),
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.checklists')
            ->with('success', 'Checklist item added successfully.');
    }

    public function updateChecklistItem(Request $request, $id)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'label' => 'required|string|max:255',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $checklistItem = \App\Models\ChecklistItem::findOrFail($id);
        $checklistItem->update([
            'label' => $request->label,
            'is_required' => $request->boolean('is_required'),
            'sort_order' => $request->sort_order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.checklists')
            ->with('success', 'Checklist item updated successfully.');
    }

    public function destroyChecklistItem($id)
    {
        $this->ensureAdminAccess();

        $checklistItem = \App\Models\ChecklistItem::findOrFail($id);
        
        // Check if related checklist reviews exist
        $hasReviews = \App\Models\ChecklistReview::where('checklist_item_id', $id)->exists();
        
        if ($hasReviews) {
            return redirect()->route('admin.checklists')
                ->with('error', 'Cannot delete checklist item that has existing reviews. Please archive it instead.');
        }

        // Record audit log before deletion
        $adminUser = auth()->user();
        \App\Models\AuditLog::create([
            'actor_id' => $adminUser->id,
            'actor_role' => $adminUser->role,
            'action' => 'deleted_checklist_item',
            'note' => "Deleted checklist item: {$checklistItem->label} (ID: {$checklistItem->id})",
            'created_at' => now(),
        ]);

        // Perform soft delete
        $checklistItem->delete();

        return redirect()->route('admin.checklists')
            ->with('success', 'Checklist item deleted successfully.');
    }

    public function reorderChecklistItems(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:checklist_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            \App\Models\ChecklistItem::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
