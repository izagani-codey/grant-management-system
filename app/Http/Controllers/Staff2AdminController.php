<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\RequestTypeTemplate;
use App\Models\User;
use App\Models\Document;
use App\Models\AuditLog;
use App\Models\Signatory;
use App\Services\PdfInfoService;
use App\Traits\ResolvesPresetZones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Staff2AdminController extends BaseController
{
    use ResolvesPresetZones;
    
    private const MANAGEABLE_ROLES = ['admission', 'staff1', 'staff2', 'admin'];

    public function index()
    {
        $this->ensureAdminAccess();

        // System Stats (read-only visibility for admin)
        $totalRequests  = GrantRequest::count();
        $submitted      = GrantRequest::where('status_id', RequestStatus::SUBMITTED->value)->count();
        $staff1Reviewed = GrantRequest::where('status_id', RequestStatus::STAFF1_REVIEWED->value)->count();
        $staff2Approved = GrantRequest::where('status_id', RequestStatus::STAFF2_APPROVED->value)->count();
        $completed      = GrantRequest::where('status_id', RequestStatus::COMPLETED->value)->count();
        $declined       = GrantRequest::where('status_id', RequestStatus::DECLINED->value)->count();

        // User Stats
        $totalUsers     = User::count();
        $admissionUsers = User::where('role', 'admission')->count();
        $staff1Users    = User::where('role', 'staff1')->count();
        $staff2Users    = User::where('role', 'staff2')->count();

        // Recent Requests (read-only)
        $recentRequests = GrantRequest::query()
            ->with('user', 'requestType')
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalRequests',
            'submitted',
            'staff1Reviewed',
            'staff2Approved',
            'completed',
            'declined',
            'totalUsers',
            'admissionUsers',
            'staff1Users',
            'staff2Users',
            'recentRequests'
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
        $this->ensureStaff2OrAdminAccess();

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
        $this->ensureStaff2OrAdminAccess();
        try {
            $req = request();
            $validated = $req->validate([
                'name'        => 'required|string|max:255|unique:request_types',
                'description' => 'nullable|string',
            ]);

            $validated['slug']               = \Str::slug($validated['name']);
            $validated['requires_vot']        = $req->boolean('requires_vot');
            $validated['requires_signature']  = $req->boolean('requires_signature');
            $validated['is_active']           = true;

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
        $this->ensureStaff2OrAdminAccess();
        try {
            $requestType = RequestType::findOrFail($id);
            
            $req = request();
            $validated = $req->validate([
                'name'               => 'required|string|max:255|unique:request_types,name,' . $id,
                'description'        => 'nullable|string',
                'default_template_id'=> 'nullable|exists:documents,id',
                'required_documents' => 'nullable|array',
                'required_documents.*' => 'string|max:255',
                'field_schema'       => 'nullable|string',
            ]);

            $validated['slug'] = \Str::slug($validated['name']);

            $validated['required_documents'] = array_values(
                array_filter($validated['required_documents'] ?? [], fn ($d) => trim($d) !== '')
            ) ?: null;

            $fieldSchema = null;
            if (!empty($validated['field_schema'])) {
                $decoded = json_decode($validated['field_schema'], true);
                $fieldSchema = is_array($decoded) ? $decoded : null;
            }

            $requestType->update([
                'name'                => $validated['name'],
                'slug'                => $validated['slug'],
                'description'         => $validated['description'] ?? null,
                'default_template_id' => $validated['default_template_id'] ?? null,
                'required_documents'  => $validated['required_documents'],
                'requires_vot'        => $req->boolean('requires_vot'),
                'requires_signature'  => $req->boolean('requires_signature'),
                'is_active'           => $req->boolean('is_active'),
                ...($fieldSchema !== null ? ['field_schema' => $fieldSchema] : []),
            ]);

            return back()->with('success', 'Request type updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating request type: ' . $e->getMessage());
            return back()->with('error', 'Unable to update request type at the moment. Please try again.')->withInput();
        }
    }

    public function destroyRequestType($id)
    {
        $this->ensureStaff2OrAdminAccess();
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

    public function settings()
    {
        $this->ensureAdminAccess();
        $settings = \App\Services\SettingsService::all();
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(\Illuminate\Http\Request $request)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'app_name'              => 'nullable|string|max:255',
            'institution_name'      => 'nullable|string|max:255',
            'institution_tagline'   => 'nullable|string|max:255',
            'primary_color'         => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color'          => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'footer_text'           => 'nullable|string|max:500',
            'support_email'         => 'nullable|email|max:255',
            'allowed_email_domains' => 'nullable|string|max:500',
        ]);

        foreach ($validated as $key => $value) {
            \App\Services\SettingsService::set($key, $value ?? '');
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Settings saved.');
    }

    public function uploadSettingsImage(\Illuminate\Http\Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'type'  => 'required|in:app_logo,app_favicon',
            'image' => 'required|image|max:2048|mimes:png,jpg,jpeg,svg',
        ]);

        $path = $request->file('image')->store('branding', 'public');
        \App\Services\SettingsService::set($request->type, $path);

        return redirect()->route('admin.settings')
            ->with('success', 'Image uploaded.');
    }

    private function ensureAdminAccess(): void
    {
        if (!auth()->user()?->canAccessAdminPanel()) {
            abort(403, 'Unauthorized access to admin panel');
        }
    }

    private function ensureStaff2OrAdminAccess(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isStaff2() && !$user->canAccessAdminPanel())) {
            abort(403, 'Unauthorized');
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
        $this->ensureStaff2OrAdminAccess();

        $requestTypes = RequestType::with(['checklistItems' => function($query) {
            $query->orderBy('sort_order');
        }])->get();

        return view('admin.checklists', compact('requestTypes'));
    }

    public function storeChecklistItem(Request $request)
    {
        $this->ensureStaff2OrAdminAccess();

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
        $this->ensureStaff2OrAdminAccess();

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
        $this->ensureStaff2OrAdminAccess();

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
        $this->ensureStaff2OrAdminAccess();

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:checklist_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                \App\Models\ChecklistItem::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json(['success' => true]);
    }

    // ==========================================
    // Template Signature Zone Configuration
    // ==========================================

    public function updateTemplateZones(Request $request, Document $document)
    {
        $this->ensureStaff2OrAdminAccess();

        if ($document->document_type !== \App\Enums\DocumentType::Template) {
            abort(400, 'Only template documents support signature zones.');
        }

        $validated = $request->validate([
            'applicant_page'   => 'nullable|integer|min:1',
            'applicant_x'      => 'nullable|numeric|min:0',
            'applicant_y'      => 'nullable|numeric|min:0',
            'applicant_width'  => 'nullable|numeric|min:5',
            'applicant_height' => 'nullable|numeric|min:5',
            'staff2_page'      => 'nullable|integer|min:1',
            'staff2_x'         => 'nullable|numeric|min:0',
            'staff2_y'         => 'nullable|numeric|min:0',
            'staff2_width'     => 'nullable|numeric|min:5',
            'staff2_height'    => 'nullable|numeric|min:5',
        ]);

        $zones = [];

        if (!empty($validated['applicant_page'])) {
            $zones['applicant'] = [
                'page'   => (int)   $validated['applicant_page'],
                'x'      => (float) ($validated['applicant_x']     ?? 10),
                'y'      => (float) ($validated['applicant_y']      ?? 240),
                'width'  => (float) ($validated['applicant_width']  ?? 70),
                'height' => (float) ($validated['applicant_height'] ?? 25),
            ];
        }

        if (!empty($validated['staff2_page'])) {
            $zones['staff2'] = [
                'page'   => (int)   $validated['staff2_page'],
                'x'      => (float) ($validated['staff2_x']     ?? 110),
                'y'      => (float) ($validated['staff2_y']      ?? 240),
                'width'  => (float) ($validated['staff2_width']  ?? 70),
                'height' => (float) ($validated['staff2_height'] ?? 25),
            ];
        }

        $document->update(['signature_zones' => empty($zones) ? null : $zones]);

        return redirect()->back()->with('success', 'Signature zones saved for "' . ($document->name ?: $document->original_name) . '".');
    }

    // ==========================================
    // Zone Designer
    // ==========================================

    public function showZoneDesigner(Document $document)
    {
        abort_if(!auth()->user()->isStaff2() && !auth()->user()->canAccessAdminPanel(), 403);

        $isExcel = $document->isExcelDocument();

        if (!$isExcel && $document->pdf_page_count === null) {
            try {
                $count = app(PdfInfoService::class)->getPageCount($document->file_path);
                $document->update(['pdf_page_count' => $count]);
            } catch (\Throwable) {
                // leave null — view will default to 1
            }
        }

        $existingZones       = $document->zones ?? [];
        $fieldSchema         = $document->requestType?->field_schema ?? [];
        $pageCount           = $isExcel ? 1 : ($document->pdf_page_count ?? 1);
        $firstPageDimensions = !$isExcel
            ? app(PdfInfoService::class)->getPageDimensions($document->file_path)
            : ['width' => 210, 'height' => 297];

        // Build preset tools from enabled fields
        $presetMap = [
            'applicant_name'               => 'Applicant Name',
            'applicant_staff_id'           => 'Staff ID',
            'applicant_designation'        => 'Designation',
            'applicant_department'         => 'Department',
            'applicant_phone'              => 'Phone',
            'applicant_employee_level'     => 'Employee Level',
            'submission_date'              => 'Submission Date',
            'reference_number'             => 'Reference Number',
            'final_signatory_name'         => 'Final Signatory Name',
            'final_signatory_designation'  => 'Final Signatory Designation',
            'second_signatory_name'        => 'Second Signatory Name',
            'second_signatory_designation' => 'Second Signatory Designation',
        ];

        $presetTools = collect($document->preset_config ?? [])
            ->filter(fn($enabled) => $enabled)
            ->map(fn($enabled, $key) => [
                'tool'  => 'preset_' . $key,
                'label' => $presetMap[$key] ?? $key,
            ])
            ->values();

        // Flash warning if preset not configured
        if (!$document->preset_config) {
            session()->flash('warning', 'Preset fields not configured. Configure them first for best results.');
        }

        return view('staff2.zone-designer', compact(
            'document', 'existingZones', 'fieldSchema', 'pageCount', 'firstPageDimensions', 'isExcel', 'presetTools'
        ));
    }

    public function saveZones(Request $httpRequest, Document $document)
    {
        abort_if(!auth()->user()->isStaff2() && !auth()->user()->canAccessAdminPanel(), 403);

        $httpRequest->validate([
            'zones'           => ['required', 'array'],
            'zones.*'         => ['array'],
            'zones.*.*'       => ['array'],
            'zones.*.*.nx'    => ['numeric', 'between:0,1'],
            'zones.*.*.ny'    => ['numeric', 'between:0,1'],
            'zones.*.*.nw'    => ['numeric', 'between:0.01,1'],
            'zones.*.*.nh'    => ['numeric', 'between:0.01,1'],
            'zones.*.*.tool'  => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $valid = in_array($value, ['applicant_signature', 'staff2_signature'], true)
                        || str_starts_with($value, 'field_');
                    if (!$valid) {
                        $fail("Invalid zone tool: {$value}");
                    }
                },
            ],
        ]);

        $document->update(['zones' => $httpRequest->zones]);

        $totalZones = collect($httpRequest->zones)->flatten(1)->count();

        return response()->json([
            'success' => true,
            'message' => "Saved {$totalZones} zone" . ($totalZones !== 1 ? 's' : ''),
        ]);
    }

    public function servePdf(Document $document)
    {
        abort_if(!auth()->user()->isStaff2() && !auth()->user()->canAccessAdminPanel(), 403);
        abort_if($document->document_type !== \App\Enums\DocumentType::Template, 403);

        $path = Storage::disk('public')->path($document->file_path);
        abort_if(!file_exists($path), 404);

        $mime = $document->isExcelDocument()
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/pdf';

        return response()->file($path, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
        ]);
    }

    public function updateTemplateFieldZones(Request $request, Document $document)
    {
        $this->ensureStaff2OrAdminAccess();

        if ($document->document_type !== \App\Enums\DocumentType::Template) {
            abort(400, 'Only template documents support field zones.');
        }

        $raw   = $request->input('field_zones', []);
        $zones = [];
        foreach ($raw as $fieldName => $z) {
            if (!isset($z['x']) || !isset($z['y'])) continue;
            $zones[$fieldName] = [
                'page'      => max(1,  (int)   ($z['page']      ?? 1)),
                'x'         => max(0,  (float) ($z['x']         ?? 0)),
                'y'         => max(0,  (float) ($z['y']         ?? 0)),
                'width'     => max(5,  (float) ($z['width']     ?? 60)),
                'height'    => max(4,  (float) ($z['height']    ?? 8)),
                'font_size' => max(6, min(24, (float) ($z['font_size'] ?? 10))),
            ];
        }

        $document->update(['field_zones' => empty($zones) ? null : $zones]);

        return redirect()->back()->with('success', 'Field zones saved for "' . ($document->name ?: $document->original_name) . '".');
    }

    // ==========================================
    // Signatory Management Methods
    // ==========================================

    public function signatories()
    {
        $this->ensureStaff2OrAdminAccess();

        $signatories = Signatory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('staff2.signatories', compact('signatories'));
    }

    public function storeSignatory(Request $request)
    {
        $this->ensureStaff2OrAdminAccess();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'staff_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Signatory::create($validated);

        return redirect()->back()->with('success', 'Signatory added successfully.');
    }

    public function updateSignatory(Request $request, Signatory $signatory)
    {
        $this->ensureStaff2OrAdminAccess();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'staff_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $signatory->update($validated);

        return redirect()->back()->with('success', 'Signatory updated successfully.');
    }

    public function destroySignatory(Signatory $signatory)
    {
        $this->ensureStaff2OrAdminAccess();

        $signatory->delete();

        return redirect()->back()->with('success', 'Signatory deleted successfully.');
    }

    public function importSignatories(Request $request)
    {
        $this->ensureStaff2OrAdminAccess();

        $validated = $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048',
        ]);

        $file = $request->file('file');
        $imported = 0;

        if ($file->getClientOriginalExtension() === 'csv') {
            $csv = array_map('str_getcsv', file($file->getPathname()));
            $headers = array_shift($csv);
            
            foreach ($csv as $row) {
                if (count($row) < 3) continue; // Need at least name and designation
                
                $rowData = array_combine($headers, $row);
                
                if (empty($rowData['name']) || empty($rowData['designation'])) continue;

                Signatory::updateOrCreate(
                    [
                        'staff_id' => $rowData['staff_id'] ?? null,
                        'name' => $rowData['name']
                    ],
                    [
                        'designation' => $rowData['designation'],
                        'title' => $rowData['title'] ?? null,
                        'department' => $rowData['department'] ?? null,
                        'is_active' => true
                    ]
                );
                
                $imported++;
            }
        } else {
            // For Excel files, you would need to install and use a package like maatwebsite/excel
            return redirect()->back()->with('error', 'Excel import requires additional package. Please use CSV format.');
        }

        return redirect()->back()->with('success', "Imported {$imported} signatories successfully.");
    }

    // ==========================================
    // Preset Configuration Methods
    // ==========================================

    public function showPresetConfig(Document $document)
    {
        abort_if(!auth()->user()->isStaff2() && !auth()->user()->canAccessAdminPanel(), 403);

        $document->load('requestType');

        $defaults = [
            'applicant_name' => false,
            'applicant_staff_id' => false,
            'applicant_designation' => false,
            'applicant_department' => false,
            'applicant_phone' => false,
            'applicant_employee_level' => false,
            'submission_date' => false,
            'reference_number' => false,
            'final_signatory_name' => false,
            'final_signatory_designation' => false,
            'second_signatory_name' => false,
            'second_signatory_designation' => false,
        ];
        
        $config = array_merge($defaults, $document->preset_config ?? []);

        return view('staff2.preset-config', compact('document', 'config'));
    }

    public function savePresetConfig(Request $request, Document $document)
    {
        abort_if(!auth()->user()->isStaff2() && !auth()->user()->canAccessAdminPanel(), 403);

        $validated = $request->validate([
            'applicant_name' => 'nullable|boolean',
            'applicant_staff_id' => 'nullable|boolean',
            'applicant_designation' => 'nullable|boolean',
            'applicant_department' => 'nullable|boolean',
            'applicant_phone' => 'nullable|boolean',
            'applicant_employee_level' => 'nullable|boolean',
            'submission_date' => 'nullable|boolean',
            'reference_number' => 'nullable|boolean',
            'final_signatory_name' => 'nullable|boolean',
            'final_signatory_designation' => 'nullable|boolean',
            'second_signatory_name' => 'nullable|boolean',
            'second_signatory_designation' => 'nullable|boolean',
        ]);

        $document->update(['preset_config' => $request->only([
            'applicant_name', 'applicant_staff_id',
            'applicant_designation', 'applicant_department',
            'applicant_phone', 'applicant_employee_level',
            'submission_date', 'reference_number',
            'final_signatory_name', 'final_signatory_designation',
            'second_signatory_name', 'second_signatory_designation',
        ])]);

        if ($request->has('redirect_to_zones')) {
            return redirect()->route('staff2.zones.edit', $document->id)
                ->with('success', 'Preset configuration saved successfully.');
        }

        return redirect()->back()
            ->with('success', 'Preset configuration saved successfully.');
    }

    // ==========================================
    // Pre-filled Download
    // ==========================================

    public function downloadPrefilled(Request $httpRequest, GrantRequest $grantRequest)
    {
        // Gate: staff1 or staff2 only (both can download)
        abort_if(!auth()->user()->isStaff1() && !auth()->user()->isStaff2(), 403);

        // Load template document for this request type
        $template = Document::where('request_type_id', $grantRequest->request_type_id)
            ->where('document_type', DocumentType::Template->value)
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$template) {
            \Log::warning('Pre-fill download: no template found', [
                'request_id' => $grantRequest->id,
                'request_type_id' => $grantRequest->request_type_id,
            ]);
            abort(404, 'No template found for this request type');
        }

        // Load $grantRequest with user and requestType
        $grantRequest->load(['user', 'requestType']);

        // If template is PDF
        if ($template->isPdf()) {
            return $this->downloadPrefilledPdf($template, $grantRequest);
        }

        // If template is XLS/XLSX
        if ($template->isExcelDocument()) {
            return $this->downloadPrefilledXls($template, $grantRequest);
        }

        abort(500, 'Unsupported template format');
    }

    private function downloadPrefilledPdf(Document $template, GrantRequest $grantRequest)
    {
        try {
            $pdf = new Fpdi('P', 'mm');
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile(Storage::disk('public')->path($template->file_path));

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl  = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                $pageW = (float) $size['width'];
                $pageH = (float) $size['height'];

                // Stamp preset zones
                if (!empty($template->zones)) {
                    $pageIndex = $pageNo - 1;
                    $pageZones = $template->zones[$pageIndex] ?? [];

                    foreach ($pageZones as $zone) {
                        if (!str_starts_with($zone['tool'], 'preset_')) continue;

                        $x = (float) $zone['nx'] * $pageW;
                        $y = (float) $zone['ny'] * $pageH;
                        $w = (float) $zone['nw'] * $pageW;
                        $h = (float) $zone['nh'] * $pageH;

                        $value = $this->resolvePresetValue($zone['tool'], $grantRequest);
                        if ($value !== '') {
                            $fontSize = max(8, $h * 2.8);
                            $pdf->SetFont('Helvetica', '', $fontSize);
                            $pdf->SetTextColor(30, 30, 30);
                            $pdf->SetXY($x, $y);
                            $pdf->Cell($w, $h, $value, 0, 0, 'L');
                        }
                    }
                }

                // Stamp field zones
                foreach ($template->zones[$pageNo - 1] ?? [] as $zone) {
                    if (!str_starts_with($zone['tool'], 'field_')) continue;

                    $fieldName = substr($zone['tool'], 6);
                    $value = (string) ($grantRequest->field_values[$fieldName] ?? '');
                    if ($value !== '') {
                        $x = (float) $zone['nx'] * $pageW;
                        $y = (float) $zone['ny'] * $pageH;
                        $w = (float) $zone['nw'] * $pageW;
                        $h = (float) $zone['nh'] * $pageH;

                        $fontSize = max(8, $h * 2.8);
                        $pdf->SetFont('Helvetica', '', $fontSize);
                        $pdf->SetTextColor(30, 30, 30);
                        $pdf->SetXY($x, $y);
                        $pdf->Cell($w, $h, $value, 0, 0, 'L');
                    }
                }
            }

            $filename = 'prefilled_' . $grantRequest->ref_number . '.pdf';
            $pdf->Output($filename, 'D');
            exit;

        } catch (\Throwable $e) {
            report($e);
            abort(500, 'Failed to generate pre-filled PDF');
        }
    }

    private function downloadPrefilledXls(Document $template, GrantRequest $grantRequest)
    {
        try {
            $sourcePath = Storage::disk('public')->path($template->file_path);
            $spreadsheet = IOFactory::load($sourcePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Initialize zones array
            $allZones = [];

            // Stamp preset zones
            if (!empty($template->zones)) {
                foreach ($template->zones as $pageZones) {
                    if (is_array($pageZones)) {
                        $allZones = array_merge($allZones, $pageZones);
                    }
                }

                foreach ($allZones as $zone) {
                    if (!str_starts_with($zone['tool'], 'preset_')) continue;
                    if (empty($zone['cell_start'])) continue;

                    $value = $this->resolvePresetValue($zone['tool'], $grantRequest);
                    if ($value !== '') {
                        $sheet->setCellValue($zone['cell_start'], $value);
                        $sheet->getStyle($zone['cell_start'])
                            ->getFont()->setSize(max(8, 20));
                    }
                }
            }

            // Stamp field zones
            foreach ($allZones as $zone) {
                if (!str_starts_with($zone['tool'], 'field_')) continue;
                if (empty($zone['cell_start'])) continue;

                $fieldName = substr($zone['tool'], 6);
                $value = (string) ($grantRequest->field_values[$fieldName] ?? '');
                if ($value !== '') {
                    $sheet->setCellValue($zone['cell_start'], $value);
                }
            }

            $filename = 'prefilled_' . $grantRequest->ref_number . '.xlsx';
            $writer = new XlsxWriter($spreadsheet);
            $writer->save($filename);
            return response()->download($filename)->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            report($e);
            abort(500, 'Failed to generate pre-filled XLSX');
        }
    }
}
