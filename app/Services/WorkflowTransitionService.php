<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\Signature;
use App\Models\User;
use App\Services\RequestPdfService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkflowTransitionService
{
    /**
     * Define allowed transitions for each role
     * SINGLE SOURCE OF TRUTH for workflow rules
     */
    public static function getAllowedTransitions(): array
    {
        return [
            'admission' => [
                RequestStatus::RETURNED->value => [
                    RequestStatus::SUBMITTED->value
                ]
            ],
            'staff1' => [
                RequestStatus::SUBMITTED->value => [
                    RequestStatus::STAFF1_APPROVED->value, 
                    RequestStatus::RETURNED->value
                ]
            ],
            'staff2' => [
                RequestStatus::SUBMITTED->value => [
                    RequestStatus::STAFF2_APPROVED->value, // override
                    RequestStatus::RETURNED->value, 
                    RequestStatus::REJECTED->value
                ],  
                RequestStatus::STAFF1_APPROVED->value => [
                    RequestStatus::STAFF2_APPROVED->value, 
                    RequestStatus::RETURNED->value, 
                    RequestStatus::REJECTED->value
                ]
            ],
            'dean' => [
                RequestStatus::STAFF2_APPROVED->value => [
                    RequestStatus::DEAN_APPROVED->value, 
                    RequestStatus::RETURNED->value, 
                    RequestStatus::REJECTED->value
                ]
            ]
        ];
    }

    /**
     * Check if a transition is allowed for a user
     */
    public static function canTransition(GrantRequest $request, RequestStatus $newStatus, User $user): bool
    {
        $transitions = self::getAllowedTransitions();
        $roleTransitions = $transitions[$user->role] ?? [];

        if ($user->role === 'dean' && !$request->requiresDeanSignature()) {
            return false;
        }

        if (!isset($roleTransitions[$request->status_id])) {
            return false;
        }

        return in_array($newStatus->value, $roleTransitions[$request->status_id]);
    }

    
    /**
     * SINGLE ENTRY POINT for ALL workflow transitions
     * Handles: validation, signatures, override, audit, notifications, PDF
     */
    public static function executeTransition(GrantRequest $request, RequestStatus $newStatus, array $data = []): GrantRequest
    {
        $user = Auth::user();
        $workflowPolicy = $request->requestType()->with('workflowPolicy')->first()?->workflowPolicy;

        if ($workflowPolicy === null) {
            throw new \RuntimeException("No workflow policy configured for request type {$request->request_type_id}");
        }
        
        // 1. Validate transition authorization
        if (!self::canTransition($request, $newStatus, $user)) {
            throw new AuthorizationException('You are not authorized to perform this status transition.');
        }

        // 2. Validate signature requirement for Staff2 and Dean
        self::validateSignatureRequirement($user, $newStatus, $data);

        $oldStatus = RequestStatus::from($request->status_id);
        
        // 3. Detect Staff2 override (SUBMITTED -> STAFF2_APPROVED)
        $isOverride = $user->role === 'staff2' && $oldStatus === RequestStatus::SUBMITTED && $newStatus === RequestStatus::STAFF2_APPROVED;

        DB::transaction(function () use ($request, $newStatus, $data, $user, $oldStatus, $isOverride): void {
            // 4. Create comprehensive audit log
            self::createAuditLog($request, $oldStatus, $newStatus, $user, $data, $isOverride);

            // 5. Update request status and metadata
            $request->update([
                'status_id' => $newStatus->value,
                'staff_notes' => $data['notes'] ?? $request->staff_notes,
                'rejection_reason' => $data['rejection_reason'] ?? $request->rejection_reason,
            ]);

            // 6. Update role-specific tracking fields
            self::updateTrackingFields($request, $newStatus, $user, $isOverride);

            // 7. Save stage signatures if provided
            self::saveStageSignatures($request, $user, $data);
        });

        // 8. Dispatch notifications (best-effort; failure doesn't block transition)
        self::dispatchNotifications($request, $oldStatus, $newStatus);

        // 9. Regenerate PDF if signature was added
        self::regeneratePdfIfNeeded($request, $user, $data);

        return $request->fresh();
    }

    /**
     * Validate signature requirement for Staff2 and Dean approval/rejection
     */
    private static function validateSignatureRequirement(User $user, RequestStatus $newStatus, array $data): void
    {
        // Staff1 does NOT require signature
        if ($user->role === 'staff1') {
            return;
        }

        // Staff2 requires signature for STAFF2_APPROVED and REJECTED
        if ($user->role === 'staff2') {
            $requiresSignature = in_array($newStatus, [
                RequestStatus::STAFF2_APPROVED,
                RequestStatus::REJECTED,
            ], true);
            
            if (!$requiresSignature) {
                return;
            }
            
            if (empty($data['staff2_signature_data'])) {
                throw new AuthorizationException(
                    "Staff 2 signature is required to " . 
                    ($newStatus === RequestStatus::REJECTED ? 'reject' : 'approve') . 
                    " this request."
                );
            }
            return;
        }

        // Dean requires signature for DEAN_APPROVED and REJECTED
        if ($user->role === 'dean') {
            $requiresSignature = in_array($newStatus, [
                RequestStatus::DEAN_APPROVED,
                RequestStatus::REJECTED,
            ], true);
            
            if (!$requiresSignature) {
                return;
            }
            
            if (empty($data['dean_signature_data'])) {
                throw new AuthorizationException(
                    "Dean signature is required to " . 
                    ($newStatus === RequestStatus::REJECTED ? 'reject' : 'approve') . 
                    " this request."
                );
            }
            return;
        }
    }

    /**
     * Create comprehensive audit log entry
     */
    private static function createAuditLog(GrantRequest $request, RequestStatus $from, RequestStatus $to, User $user, array $data, bool $isOverride = false): void
    {
        $action = self::getActionType($from, $to, $isOverride);
        
        \App\Models\AuditLog::create([
            'request_id' => $request->id,
            'actor_id' => $user->id,
            'actor_role' => $user->role,
            'action' => $action,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'note' => $data['notes'] ?? null,
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'is_override' => $isOverride,
            'signature_data' => self::getSignatureDataForAudit($user->role, $data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Determine action type for audit logging
     */
    private static function getActionType(RequestStatus $from, RequestStatus $to, bool $isOverride): string
    {
        if ($isOverride) {
            return 'override_staff1';
        }
        
        return match($to) {
            RequestStatus::STAFF1_APPROVED => 'staff1_approved',
            RequestStatus::STAFF2_APPROVED => 'staff2_approved',
            RequestStatus::DEAN_APPROVED => 'dean_approved',
            RequestStatus::RETURNED => 'returned',
            RequestStatus::REJECTED => 'rejected',
            RequestStatus::SUBMITTED => 'resubmitted',
            default => 'status_changed',
        };
    }

    /**
     * Get signature data for audit logging
     */
    private static function getSignatureDataForAudit(string $role, array $data): ?string
    {
        $signatureField = match ($role) {
            'staff2' => 'staff2_signature_data',
            'dean' => 'dean_signature_data',
            default => null,
        };

        return $signatureField && !empty($data[$signatureField]) ? 'signature_provided' : null;
    }

    /**
     * Update role-specific tracking fields
     */
    private static function updateTrackingFields(GrantRequest $request, RequestStatus $newStatus, User $user, bool $isOverride = false): void
    {
        if ($newStatus === RequestStatus::STAFF1_APPROVED && !$isOverride) {
            $request->update(['verified_by' => $user->id, 'verified_at' => now()]);
        } elseif ($newStatus === RequestStatus::STAFF2_APPROVED) {
            $request->update([
                'recommended_by' => $user->id,
                'recommended_at' => now(),
                'is_override' => $isOverride,
            ]);
        } elseif ($newStatus === RequestStatus::DEAN_APPROVED) {
            $request->update([
                'dean_approved_by' => $user->id,
                'dean_approved_at' => now(),
            ]);
        }
    }

    /**
     * Save stage signatures based on user role
     */
    private static function saveStageSignatures(GrantRequest $request, User $user, array $data): void
    {
        $signatureField = match ($user->role) {
            'staff1' => 'staff1_signature_data',
            'staff2' => 'staff2_signature_data',
            'dean'   => 'dean_signature_data',
            default => null,
        };

        $timestampField = match ($user->role) {
            'staff1' => 'staff1_signed_at',
            'staff2' => 'staff2_signed_at',
            'dean'   => 'dean_signed_at',
            default => null,
        };

        if ($signatureField && $timestampField && isset($data[$signatureField])) {
            $signatureValue = is_string($data[$signatureField]) ? trim($data[$signatureField]) : '';
            if ($signatureValue === '') {
                return;
            }

            $signedAt = now();

            // Backward compatibility with legacy request columns.
            $request->update([
                $signatureField => $signatureValue,
                $timestampField => $signedAt,
            ]);

            // Normalized signature storage.
            Signature::updateOrCreate(
                [
                    'request_id' => $request->id,
                    'role' => $user->role,
                ],
                [
                    'user_id' => $user->id,
                    'signature_path' => $signatureValue,
                    'signed_at' => $signedAt,
                ]
            );
        }
    }

    /**
     * Dispatch notifications based on transition
     */
    private static function dispatchNotifications(GrantRequest $request, RequestStatus $from, RequestStatus $to): void
    {
        try {
            if ($to === RequestStatus::STAFF1_APPROVED) {
                self::notifyRole('staff2', $request, 'request_ready_for_recommendation', 
                    'Request Ready for Recommendation', 
                    "Request {$request->ref_number} is ready for your review.");
            } elseif ($to === RequestStatus::STAFF2_APPROVED) {
                if ($request->requiresDeanSignature()) {
                    self::notifyRole('dean', $request, 'request_pending_dean_approval',
                        'Request Pending Dean Approval',
                        "Request {$request->ref_number} is ready for your final approval.");
                } else {
                    self::notifyAdmission($request, 'Request approved');
                }
            } elseif ($to === RequestStatus::RETURNED) {
                self::notifyAdmission($request, 'Request returned for revision');
            } elseif ($to === RequestStatus::DEAN_APPROVED) {
                self::notifyAdmission($request, 'Request approved');
            } elseif ($to === RequestStatus::REJECTED) {
                self::notifyAdmission($request, 'Request declined');
            }
        } catch (\Throwable $e) {
            \Log::warning('Workflow transition notification dispatch failed', [
                'request_id' => $request->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify all users with a specific role
     */
    private static function notifyRole(string $role, GrantRequest $request, string $type, string $title, string $message): void
    {
        $users = \App\Models\User::where('role', $role)->get();
        $url = route('requests.show', $request->id);

        foreach ($users as $user) {
            \App\Models\Notification::createForUser(
                $user->id,
                $type,
                $title,
                $message,
                $url,
                ['request_id' => $request->id]
            );
        }
    }

    /**
     * Notify admission user
     */
    private static function notifyAdmission(GrantRequest $request, string $title): void
    {
        \App\Models\Notification::createForUser(
            $request->user_id,
            'request_updated',
            $title,
            "Request {$request->ref_number} has been updated. Please review the changes.",
            route('requests.show', $request->id),
            ['request_id' => $request->id]
        );
    }

    /**
     * Regenerate PDF if signature was added
     */
    private static function regeneratePdfIfNeeded(GrantRequest $request, User $user, array $data): void
    {
        if (!self::hasSignatureData($user->role, $data)) {
            return;
        }

        try {
            $template = $request->requestType?->getDefaultTemplate();
            if ($template) {
                RequestPdfService::generate($request, $template);
            }
        } catch (\Throwable $e) {
            \Log::warning('PDF regeneration failed after signature', [
                'request_id' => $request->id,
                'user_role' => $user->role,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if signature data is present for the user role
     */
    private static function hasSignatureData(string $role, array $data): bool
    {
        $signatureField = match ($role) {
            'staff2' => 'staff2_signature_data',
            'dean' => 'dean_signature_data',
            default => null,
        };

        return $signatureField && !empty($data[$signatureField]);
    }
}
