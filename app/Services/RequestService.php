<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Document;
use App\Models\Request as GrantRequest;
use App\Models\Signature;
use App\Models\User;
use App\Repositories\RequestRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RequestService
{
    public function __construct(
        private RequestRepository $requestRepository,
        private WorkflowTransitionService $workflowService,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new request with comprehensive business logic
     */
    public function createRequest(array $data, User $user): GrantRequest
    {
        return DB::transaction(function () use ($data, $user) {
            // Calculate total amount from VOT items
            $totalAmount = collect($data['vot_items'])->sum(fn($item) => (float) ($item['amount'] ?? 0));

            $payload = [
                'amount' => $data['amount'] ?? $totalAmount,
                'description' => $data['description'],
                'dynamic_fields' => $data['dynamic_fields'] ?? [],
            ];

            $filePath = $this->handleFileUpload($data['document'] ?? null);

            $requestData = [
                'user_id' => $user->id,
                'request_type_id' => $data['request_type_id'],
                'status_id' => RequestStatus::SUBMITTED->value,
                'payload' => $payload,
                'vot_items' => $data['vot_items'],
                'total_amount' => $totalAmount,
                'file_path' => $filePath,
                'submitter_staff_id' => $user->staff_id,
                'submitter_designation' => $user->designation,
                'submitter_department' => $user->department,
                'submitter_phone' => $user->phone,
                'submitter_employee_level' => $user->employee_level,
                'signature_data' => $data['signature_data'],
                'signed_at' => now(),
                'submitted_at' => now(),
            ];

            $attempts = 0;
            while (true) {
                try {
                    $requestData['ref_number'] = $this->requestRepository->generateReferenceNumber();
                    $request = $this->requestRepository->create($requestData);
                    break;
                } catch (UniqueConstraintViolationException) {
                    if (++$attempts >= 5) throw new \RuntimeException('Could not generate a unique reference number.');
                }
            }

            // Store additional documents if provided
            if (isset($data['additional_documents']) && is_array($data['additional_documents'])) {
                foreach ($data['additional_documents'] as $index => $document) {
                    if ($document instanceof UploadedFile) {
                        $this->storeAdditionalDocument($request, $document, $index);
                    }
                }
            }

            // Store signature in normalized table
            $this->storeSignature($request, $user, 'applicant', $data['signature_data']);

            // Create audit log - WorkflowService handles this now
            // $this->createAuditLog($request, null, RequestStatus::SUBMITTED, 'Request submitted');

            // Send notification to staff1
            $this->notificationService->notifyNewRequest($request);

            return $request;
        });
    }

    /**
     * Update request status.
     */
    public function updateStatus(GrantRequest $request, int $statusId, User $user, array $data = []): void
    {
        $newStatus = RequestStatus::from($statusId);

        // ALL workflow actions must go through WorkflowService
        $this->workflowService->executeTransition($request, $newStatus, $data);
    }

    /**
     * Add comment to request.
     */
    public function addComment(GrantRequest $request, string $content, User $user): Comment
    {
        $comment = $request->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        // Notify relevant users
        $this->notificationService->notifyNewComment($comment);

        return $comment;
    }

    /**
     * Update request details.
     */
    public function updateRequest(GrantRequest $request, array $data, User $user): GrantRequest
    {
        $payload = [
            'amount' => $data['amount'],
            'description' => $data['description'],
        ];

        $filePath = $this->handleFileUpload($data['document'] ?? null, $request->file_path);

        $updateData = [
            'request_type_id' => $data['request_type_id'],
            'payload' => $payload,
            'file_path' => $filePath,
            'revision_count' => $request->revision_count + 1,
        ];

        $this->requestRepository->update($request, $updateData);

        // Reset status if returned to admission - use WorkflowService
        if ($request->status_id === RequestStatus::RETURNED->value) {
            $this->workflowService->executeTransition($request, RequestStatus::SUBMITTED, ['notes' => 'Resubmitted after revision']);
        }

        // Create audit log - WorkflowService handles this now
        // $this->createAuditLog($request, $request->status_id, RequestStatus::from($request->status_id), 'Request updated');

        return $request->fresh();
    }

    /**
     * Handle file upload.
     */
    private function handleFileUpload(?UploadedFile $file, ?string $existingPath = null): ?string
    {
        if (!$file) {
            return $existingPath;
        }

        // Delete old file if exists
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $file->store('requests', 'public');
    }

    /**
     * Store additional document for request
     */
    private function storeAdditionalDocument(GrantRequest $request, UploadedFile $document, int $index): void
    {
        $filename = "additional_{$index}_" . time() . '.' . $document->getClientOriginalExtension();
        $path = $document->storeAs('documents', $filename, 'public');

        Document::create([
            'request_id' => $request->id,
            'filename' => $document->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => "additional_{$index}",
            'file_size' => $document->getSize(),
            'mime_type' => $document->getMimeType(),
        ]);
    }

    /**
     * Store signature for request
     */
    private function storeSignature(GrantRequest $request, User $user, string $role, string $signatureData): void
    {
        // Update legacy field for backward compatibility
        if ($role === 'applicant') {
            $request->update(['signature_data' => $signatureData]);
        }

        // Store in normalized signatures table
        Signature::updateOrCreate(
            [
                'request_id' => $request->id,
                'role' => $role,
            ],
            [
                'user_id' => $user->id,
                'signature_path' => $signatureData,
                'signed_at' => now(),
            ]
        );
    }


    /**
     * Get requests with filtering.
     */
    public function getFilteredRequests(array $filters, User $user, int $perPage = 15)
    {
        return $this->requestRepository->getFilteredRequests($filters, $user, $perPage);
    }

    /**
     * Get request for display.
     */
    public function getRequestForDisplay(int $id): GrantRequest
    {
        return $this->requestRepository->getForDisplay($id);
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(User $user): array
    {
        return $this->requestRepository->getDashboardStats($user);
    }

}
