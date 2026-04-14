<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\Signature;
use App\Models\User;
use App\Models\VotCode;
use App\Services\NotificationService;
use App\Services\RequestPdfService;
use App\Services\WorkflowTransitionService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RequestController extends BaseController
{
public function __construct(
        private NotificationService $notificationService
    ) {}


    public function index(Request $request)
    {
    
        $query = GrantRequest::query()
            ->with(['requestType', 'user', 'verifiedBy', 'recommendedBy', 'deanApprovedBy'])
            ->latest('created_at');

        if (Auth::user()->role === 'admission') {
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status_id', (int) $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('request_type_id', (int) $request->input('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('ref_number', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return view('requests.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'requestTypes' => RequestType::where('is_active', true)->orderBy('name')->get(),
            'statuses' => RequestStatus::getAllCases(),
        ]);
    }

    public function create()
    {
        return view('requests.create', [
            'requestTypes' => RequestType::where('is_active', true)->orderBy('name')->get(),
            'votCodes' => VotCode::active()->ordered()->get(),
            'user' => Auth::user(),
        ]);
    }

    public function store(StoreRequestRequest $request)
    {
        $user = Auth::user();
        $requestType = RequestType::with('workflowPolicy')->findOrFail((int) $request->input('request_type_id'));
        $documentPath = $request->hasFile('document')
            ? $request->file('document')->store('documents', 'public')
            : null;

        $votItems = collect($request->input('vot_items', []))->values()->all();
        $totalAmount = collect($votItems)->sum(fn ($item) => (float) ($item['amount'] ?? 0));

        $grantRequest = GrantRequest::create([
            'user_id' => $user->id,
            'request_type_id' => $requestType->id,
            'ref_number' => $this->generateReferenceNumber(),
            'status_id' => RequestStatus::SUBMITTED->value,
            'snapshot_requires_dean_signature' => $requestType->workflowPolicy?->requires_dean_signature ?? true,
            'file_path' => $documentPath,
            'payload' => [
                'description' => $request->input('description'),
                'dynamic_fields' => $request->input('dynamic_fields', []),
                'email' => $user->email,
            ],
            'vot_items' => $votItems,
            'total_amount' => $totalAmount,
            'deadline' => $request->input('deadline'),
            'is_priority' => $this->isHighPriority($request->input('deadline')),
            'submitter_staff_id' => $user->staff_id,
            'submitter_designation' => $user->designation,
            'submitter_department' => $user->department,
            'submitter_phone' => $user->phone,
            'submitter_employee_level' => $user->employee_level,
            'signature_data' => $request->input('signature_data'),
            'signed_at' => now(),
            'submitted_at' => now(),
        ]);

        Signature::updateOrCreate(
            [
                'request_id' => $grantRequest->id,
                'role' => 'applicant',
            ],
            [
                'user_id' => $user->id,
                'signature_path' => $request->input('signature_data'),
                'signed_at' => now(),
            ]
        );

        AuditLog::create([
            'request_id' => $grantRequest->id,
            'actor_id' => $user->id,
            'actor_role' => $user->role,
            'action' => 'submitted',
            'from_status' => RequestStatus::DRAFT->value,
            'to_status' => RequestStatus::SUBMITTED->value,
            'note' => 'Initial submission by applicant.',
            'created_at' => now(),
        ]);

        $this->notificationService->sendRoleNotification(
            'staff1',
            'New Request Submitted',
            "Request {$grantRequest->ref_number} requires verification.",
            route('requests.show', $grantRequest->id)
        );

        $template = $grantRequest->requestType?->getDefaultTemplate();
        if ($template) {
            RequestPdfService::generate($grantRequest, $template);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Request submitted successfully.');
    }

    public function show($id)
    {
        $grantRequest = GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'deanApprovedBy',
            'comments.user',
            'auditLogs.actor',
            'templateUsages' => fn ($query) => $query->latest('created_at'),
            'signatures',
        ])->findOrFail($id);

        $this->authorize('view', $grantRequest);

        return view('requests.show', compact('grantRequest'));
    }

    public function edit($id)
    {
        $grantRequest = GrantRequest::with('requestType')->findOrFail($id);
        $this->authorize('revise', $grantRequest);

        return view('requests.edit', compact('grantRequest'));
    }

    public function update(UpdateRequestRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('revise', $grantRequest);
        $requestType = RequestType::with('workflowPolicy')->findOrFail($grantRequest->request_type_id);

        $documentPath = $grantRequest->file_path;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('documents', 'public');
        }

        $additionalDocumentPaths = collect($grantRequest->payload['additional_documents'] ?? []);
        if ($request->hasFile('additional_documents')) {
            foreach ($request->file('additional_documents') as $file) {
                if ($file->isValid()) {
                    $additionalDocumentPaths->push($file->store('documents/additional', 'public'));
                }
            }
        }

        $votItems = collect($request->input('vot_items', []))->values()->all();
        $totalAmount = collect($votItems)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
        $oldStatus = $grantRequest->status_id;

        $grantRequest->update([
            'status_id' => RequestStatus::SUBMITTED->value,
            'snapshot_requires_dean_signature' => $requestType->workflowPolicy?->requires_dean_signature ?? true,
            'file_path' => $documentPath,
            'vot_items' => $votItems,
            'total_amount' => $totalAmount,
            'payload' => [
                'description' => $request->input('description'),
                'dynamic_fields' => $request->input('dynamic_fields', []),
                'email' => Auth::user()->email,
                'additional_documents' => $additionalDocumentPaths->values()->all(),
            ],
            'deadline' => $request->input('deadline'),
            'is_priority' => $this->isHighPriority($request->input('deadline')),
            'signature_data' => $request->filled('signature_data')
                ? $request->input('signature_data')
                : $grantRequest->signature_data,
            'signed_at' => $request->filled('signature_data') ? now() : $grantRequest->signed_at,
            'submitted_at' => now(),
            'rejection_reason' => null,
            'staff_notes' => null,
            'revision_count' => (int) $grantRequest->revision_count + 1,
        ]);

        if ($request->filled('signature_data')) {
            Signature::updateOrCreate(
                [
                    'request_id' => $grantRequest->id,
                    'role' => 'applicant',
                ],
                [
                    'user_id' => Auth::id(),
                    'signature_path' => $request->input('signature_data'),
                    'signed_at' => now(),
                ]
            );
        }

        AuditLog::create([
            'request_id' => $grantRequest->id,
            'actor_id' => Auth::id(),
            'actor_role' => Auth::user()->role,
            'action' => 'resubmitted',
            'from_status' => $oldStatus,
            'to_status' => RequestStatus::SUBMITTED->value,
            'note' => 'Resubmitted after revision.',
            'created_at' => now(),
        ]);

        $this->notificationService->sendRoleNotification(
            'staff1',
            'Request Resubmitted',
            "Request {$grantRequest->ref_number} has been resubmitted and is ready for verification.",
            route('requests.show', $grantRequest->id)
        );

        return redirect()->route('dashboard')
            ->with('success', 'Request resubmitted successfully.');
    }

    public function printSummary($id)
    {
        $grantRequest = GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'deanApprovedBy',
            'auditLogs' => fn ($q) => $q->with('actor')->orderBy('created_at'),
        ])->findOrFail($id);

        $this->authorize('print', $grantRequest);

        return view('requests.print', compact('grantRequest'));
    }

    public function downloadPdf($id)
    {
        $grantRequest = GrantRequest::with([
            'user',
            'requestType',
            'verifiedBy',
            'recommendedBy',
            'deanApprovedBy',
            'signatures',
        ])->findOrFail($id);

        $this->authorize('print', $grantRequest);

        $template = $grantRequest->requestType?->getDefaultTemplate();
        $generatedPath = RequestPdfService::generate($grantRequest, $template);

        return Storage::disk('public')->download($generatedPath, basename($generatedPath));
    }

    public function showMainDocument($id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('view', $grantRequest);

        if (empty($grantRequest->file_path) || !Storage::disk('public')->exists($grantRequest->file_path)) {
            abort(404, 'Main document not found.');
        }

        return Storage::disk('public')->response(
            $grantRequest->file_path,
            basename($grantRequest->file_path),
            [],
            'inline'
        );
    }

    public function showAdditionalDocument($id, int $index)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('view', $grantRequest);

        $documents = collect($grantRequest->payload['additional_documents'] ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values();

        $documentPath = $documents->get($index);
        if (!$documentPath || !Storage::disk('public')->exists($documentPath)) {
            abort(404, 'Additional document not found.');
        }

        return Storage::disk('public')->response(
            $documentPath,
            basename($documentPath),
            [],
            'inline'
        );
    }

    public function exportExcel(Request $request)
    {
        $query = GrantRequest::query()
            ->with(['requestType', 'user', 'verifiedBy', 'recommendedBy', 'deanApprovedBy'])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status_id', (int) $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('request_type_id', (int) $request->input('type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('ref_number', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $filename = 'staff2-requests-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Reference',
                'Request Type',
                'Applicant',
                'Applicant Email',
                'Amount (RM)',
                'Status',
                'Verified By',
                'Recommended By',
                'Dean Approved By',
                'Created At',
            ]);

            $query->chunk(200, function ($rows) use ($handle): void {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->ref_number,
                        $row->requestType?->name,
                        $row->user?->name,
                        $row->user?->email,
                        number_format((float) $row->total_amount, 2, '.', ''),
                        $row->statusLabel(),
                        $row->verifiedBy?->name,
                        $row->recommendedBy?->name,
                        $row->deanApprovedBy?->name,
                        $row->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('changeStatus', $grantRequest);

        $newStatus = RequestStatus::from((int) $request->input('status_id'));

        $payload = [
            'notes' => $request->input('notes'),
            'rejection_reason' => $request->input('rejection_reason'),
            'staff2_signature_data' => $request->input('staff2_signature_data'),
            'dean_signature_data' => $request->input('dean_signature_data'),
        ];

        if ($newStatus === RequestStatus::RETURNED && empty($payload['notes']) && !empty($payload['rejection_reason'])) {
            $payload['notes'] = $payload['rejection_reason'];
        }

        try {
            WorkflowTransitionService::executeTransition($grantRequest, $newStatus, $payload);

            return redirect()->route('requests.show', $grantRequest->id)
                ->with('success', 'Request status updated successfully.');
        } catch (AuthorizationException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->back()->with('error', 'Unable to update status. Please try again.');
        }
    }

    public function updatePriority(Request $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);

        if (!in_array(Auth::user()->role, ['staff1', 'staff2'], true)) {
            abort(403, 'Only staff members can update request priority.');
        }

        if ($grantRequest->isFinal()) {
            return redirect()->back()->with('error', 'Cannot change priority for finalized requests.');
        }

        $grantRequest->update([
            'is_priority' => (bool) $request->boolean('is_priority'),
        ]);

        return redirect()->route('requests.show', $grantRequest->id)
            ->with('success', 'Priority updated successfully.');
    }

    public function addComment(StoreCommentRequest $request, $id)
    {
        $grantRequest = GrantRequest::findOrFail($id);
        $this->authorize('addComment', $grantRequest);

        // Determine comment type based on user role
        $commentType = 'internal';
        if (Auth::user()->role === 'staff1') {
            $commentType = 'staff1';
        } elseif (Auth::user()->role === 'staff2') {
            $commentType = 'staff2';
        } elseif (Auth::user()->role === 'dean') {
            $commentType = 'dean';
        }

        Comment::create([
            'request_id' => $grantRequest->id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'is_internal' => true,
            'comment_type' => $commentType,
            'created_at' => now(),
        ]);

        // Send notifications based on request stage and commenter role
        $currentStatus = RequestStatus::from($grantRequest->status_id);
        $commenter = Auth::user();

        // If Staff 1 commented, notify Staff 2 if request is at Staff 2 stage or beyond
        if ($commenter->role === 'staff1' && $currentStatus->canBeActionedByStaff2()) {
            $this->notificationService->sendRoleNotification(
                'staff2',
                'New Internal Comment',
                'Staff 1 added a comment to request ' . $grantRequest->ref_number . '.',
                route('requests.show', $grantRequest->id) . '#comments'
            );
        }

        // If Staff 2 commented, notify Staff 1 if request is at Staff 1 stage
        if ($commenter->role === 'staff2' && $currentStatus->canBeActionedByStaff1()) {
            $this->notificationService->sendRoleNotification(
                'staff1',
                'New Internal Comment',
                'Staff 2 added a comment to request ' . $grantRequest->ref_number . '.',
                route('requests.show', $grantRequest->id) . '#comments'
            );
        }

        // If Staff 2 commented and request is at Dean stage, notify Dean
        if ($commenter->role === 'staff2' && $grantRequest->canBeActionedByDean()) {
            $this->notificationService->sendRoleNotification(
                'dean',
                'New Internal Comment',
                'Staff 2 added a comment to request ' . $grantRequest->ref_number . '.',
                route('requests.show', $grantRequest->id) . '#comments'
            );
        }

        // If Dean commented, notify Staff 2 if request is still active
        if ($commenter->role === 'dean' && !$grantRequest->isFinal()) {
            $this->notificationService->sendRoleNotification(
                'staff2',
                'New Internal Comment',
                'Dean added a comment to request ' . $grantRequest->ref_number . '.',
                route('requests.show', $grantRequest->id) . '#comments'
            );
        }

        return redirect()->route('requests.show', $grantRequest->id)
            ->with('success', 'Comment added successfully.');
    }

    public function getDynamicFields($id): JsonResponse
    {
        $requestType = RequestType::findOrFail($id);
        $fields = $requestType->field_schema ?? [];

        if (empty($fields)) {
            return response()->json([
                'html' => '',
                'fields' => [],
            ]);
        }

        $html = view('components.dynamic-form-fields', [
            'fields' => $fields,
            'prefix' => 'dynamic_fields',
            'values' => [],
        ])->render();

        return response()->json([
            'html' => $html,
            'fields' => $fields,
        ]);
    }

    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'REQ-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (GrantRequest::where('ref_number', $reference)->exists());

        return $reference;
    }

    private function isHighPriority(?string $deadline): bool
    {
        if (empty($deadline)) {
            return false;
        }

        $targetDate = Carbon::parse($deadline);
        $daysUntilDeadline = now()->diffInDays($targetDate, false);

        return $daysUntilDeadline >= 0 && $daysUntilDeadline <= 3;
    }
   
 public function viewGeneratedPdf($id)
{
    $grantRequest = GrantRequest::with([
        'user', 'requestType', 'verifiedBy', 'recommendedBy', 'deanApprovedBy', 'signatures',
    ])->findOrFail($id);

    $this->authorize('print', $grantRequest);

    $template = $grantRequest->requestType?->getDefaultTemplate();
    $generatedPath = RequestPdfService::generate($grantRequest, $template);

    return Storage::disk('public')->response(
        $generatedPath,
        basename($generatedPath),
        ['Content-Type' => 'application/pdf'],
        'inline'
    );
}

    /**
     * Toggle override mode for Staff 2 users
     */
    public function toggleOverrideMode(Request $request)
    {
        // Authorization check - only Staff 2 can toggle override mode
        if (!auth()->user()->isStaff2()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        
        // Toggle override mode
        if ($user->override_enabled) {
            $user->disableOverride();
            $message = 'Override mode disabled.';
        } else {
            $user->enableOverride();
            $message = 'Override mode enabled.';
        }
        
        return redirect()->back()->with('success', $message);
    }

}
