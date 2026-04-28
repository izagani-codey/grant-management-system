<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\Signature;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkflowTransitionService
{
    /**
     * SINGLE SOURCE OF TRUTH for all workflow transitions.
     */
    public static function getAllowedTransitions(): array
    {
        return [
            'staff1' => [
                RequestStatus::SUBMITTED->value => [
                    RequestStatus::STAFF1_REVIEWED->value,
                    RequestStatus::RETURNED->value,
                    RequestStatus::DECLINED->value,
                ],
                RequestStatus::STAFF2_APPROVED->value => [
                    RequestStatus::COMPLETED->value,
                ],
            ],
            'staff2' => [
                RequestStatus::SUBMITTED->value => [
                    RequestStatus::STAFF2_APPROVED->value, // override: skip staff1
                ],
                RequestStatus::STAFF1_REVIEWED->value => [
                    RequestStatus::STAFF2_APPROVED->value,
                    RequestStatus::RETURNED->value,
                    RequestStatus::DECLINED->value,
                ],
            ],
            'admission' => [
                RequestStatus::RETURNED->value => [
                    RequestStatus::SUBMITTED->value,
                ],
            ],
        ];
    }

    public static function canTransition(GrantRequest $request, RequestStatus $newStatus, User $user): bool
    {
        $roleTransitions = self::getAllowedTransitions()[$user->role] ?? [];

        if (!isset($roleTransitions[$request->status_id])) {
            return false;
        }

        return in_array($newStatus->value, $roleTransitions[$request->status_id]);
    }

    /**
     * SINGLE ENTRY POINT for all workflow transitions.
     */
    public static function executeTransition(GrantRequest $request, RequestStatus $newStatus, array $data = []): GrantRequest
    {
        $user = Auth::user();

        $oldStatus  = null;
        $isOverride = false;

        DB::transaction(function () use ($request, $newStatus, $data, $user, &$oldStatus, &$isOverride): void {
            // Lock the row so concurrent transitions on the same request queue
            // behind this one instead of racing past the validation checks.
            $locked = GrantRequest::lockForUpdate()->findOrFail($request->id);

            // Re-read status from the locked row; the caller's in-memory model may be stale.
            $request->status_id = $locked->status_id;

            if (!self::canTransition($request, $newStatus, $user)) {
                throw new AuthorizationException('You are not authorized to perform this status transition.');
            }

            self::validateTransitionRequirements($request, $user, $newStatus, $data);

            $oldStatus  = RequestStatus::from($request->status_id);
            $isOverride = $user->role === 'staff2'
                && $oldStatus === RequestStatus::SUBMITTED
                && $newStatus === RequestStatus::STAFF2_APPROVED;

            self::createAuditLog($request, $oldStatus, $newStatus, $user, $data, $isOverride);

            $updateData = [
                'status_id'   => $newStatus->value,
                'staff_notes' => $data['notes'] ?? $request->staff_notes,
            ];

            if ($newStatus === RequestStatus::RETURNED) {
                $updateData['return_reason'] = $data['return_reason'] ?? null;
            }

            if ($newStatus === RequestStatus::DECLINED) {
                $updateData['decline_reason'] = $data['decline_reason'] ?? null;
            }

            // Handle final signatory selection on STAFF2_APPROVED
            if (!empty($data['final_signatory_id'])) {
                $signatory = Signatory::find($data['final_signatory_id']);
                if ($signatory) {
                    $updateData['final_signatory_id'] = $signatory->id;
                    $updateData['final_signatory_name'] = 
                        $signatory->full_name ?? '';
                    $updateData['final_signatory_designation'] = 
                        $signatory->designation ?? '';
                }
            }

            if (!empty($data['checklist_data']) && $user->role === 'staff1') {
                $payload = $request->payload ?? [];
                $payload['staff1_checklist'] = $data['checklist_data'];
                $updateData['payload'] = $payload;
            }

            $request->update($updateData);

            self::updateTrackingFields($request, $newStatus, $user, $isOverride);
            self::saveStageSignatures($request, $user, $data);
        });

        if ($newStatus === RequestStatus::STAFF2_APPROVED && $request->requestType?->requires_signature) {
            try {
                app(\App\Services\DocumentSigningService::class)->stampAndStore($request);
            } catch (\Throwable $e) {
                \Log::error('DocumentSigningService threw unexpectedly', ['error' => $e->getMessage()]);
            }
        }

        self::dispatchNotifications($request, $oldStatus, $newStatus);
        return $request->fresh();
    }

    private static function validateTransitionRequirements(GrantRequest $request, User $user, RequestStatus $newStatus, array $data): void
    {
        if ($user->role === 'staff1' && $newStatus === RequestStatus::STAFF1_REVIEWED) {
            // Load all review statuses in one query; check only active checklist items.
            $reviewStatuses = $request->checklistReviews()->pluck('status', 'checklist_item_id');
            $activeItems    = ($request->requestType?->checklistItems ?? collect())->where('is_active', true);

            $flaggedLabels = $activeItems
                ->filter(fn($item) => ($reviewStatuses[$item->id] ?? null) === 'flagged')
                ->pluck('label');

            if ($flaggedLabels->isNotEmpty()) {
                throw new AuthorizationException(
                    'Flagged items must be resolved before forwarding: ' . $flaggedLabels->implode(', ') . '.'
                );
            }

            $uncheckedLabels = $activeItems
                ->where('is_required', true)
                ->filter(fn($item) => ($reviewStatuses[$item->id] ?? null) !== 'checked')
                ->pluck('label');

            if ($uncheckedLabels->isNotEmpty()) {
                throw new AuthorizationException(
                    'Required items not yet checked: ' . $uncheckedLabels->implode(', ') . '.'
                );
            }
        }

        if ($user->role === 'staff2' && $newStatus === RequestStatus::STAFF2_APPROVED) {
            if (empty($data['staff2_signature_data'])) {
                throw new AuthorizationException('Staff 2 signature is required to approve.');
            }
            if (self::isSignatureBlank($data['staff2_signature_data'])) {
                throw new AuthorizationException('Signature appears blank. Please draw your signature before approving.');
            }
        }

        if ($newStatus === RequestStatus::SUBMITTED && $request->requestType?->requires_signature) {
            $applicantSig = $request->getSignatureImageForRole('applicant');
            if (empty($applicantSig) || self::isSignatureBlank($applicantSig)) {
                throw new AuthorizationException('Applicant signature is required and cannot be blank.');
            }
        }

        if ($newStatus === RequestStatus::RETURNED && empty($data['return_reason'])) {
            throw new AuthorizationException('A reason is required when returning a request.');
        }

        if ($newStatus === RequestStatus::DECLINED && empty($data['decline_reason'])) {
            throw new AuthorizationException('A reason is required when declining a request.');
        }
    }

    private static function createAuditLog(GrantRequest $request, RequestStatus $from, RequestStatus $to, User $user, array $data, bool $isOverride = false): void
    {
        \App\Models\AuditLog::create([
            'request_id'       => $request->id,
            'actor_id'         => $user->id,
            'actor_role'       => $user->role,
            'action'           => self::getActionType($from, $to, $isOverride),
            'from_status'      => $from->value,
            'to_status'        => $to->value,
            'note'             => $data['notes'] ?? null,
            'rejection_reason' => $data['decline_reason'] ?? $data['return_reason'] ?? null,
            'is_override'      => $isOverride,
            'signature_data'   => !empty($data['staff2_signature_data']) ? 'signature_provided' : null,
            'ip_address'       => request()->ip(),
            'user_agent'       => request()->userAgent(),
            'created_at'       => now(),
        ]);
    }

    private static function getActionType(RequestStatus $from, RequestStatus $to, bool $isOverride): string
    {
        if ($isOverride) return 'override_approved';

        return match($to) {
            RequestStatus::STAFF1_REVIEWED => 'staff1_reviewed',
            RequestStatus::STAFF2_APPROVED => 'staff2_approved',
            RequestStatus::RETURNED        => 'returned',
            RequestStatus::DECLINED        => 'declined',
            RequestStatus::COMPLETED       => 'completed',
            RequestStatus::SUBMITTED       => 'resubmitted',
            default                        => 'status_changed',
        };
    }

    private static function updateTrackingFields(GrantRequest $request, RequestStatus $newStatus, User $user, bool $isOverride = false): void
    {
        if ($newStatus === RequestStatus::STAFF1_REVIEWED) {
            $request->update(['verified_by' => $user->id, 'verified_at' => now()]);
        } elseif ($newStatus === RequestStatus::STAFF2_APPROVED) {
            $request->update([
                'recommended_by' => $user->id,
                'recommended_at' => now(),
                'is_override'    => $isOverride,
            ]);
        }
    }

    private static function saveStageSignatures(GrantRequest $request, User $user, array $data): void
    {
        $signatureField = match ($user->role) {
            'staff1' => 'staff1_signature_data',
            'staff2' => 'staff2_signature_data',
            default  => null,
        };

        $timestampField = match ($user->role) {
            'staff1' => 'staff1_signed_at',
            'staff2' => 'staff2_signed_at',
            default  => null,
        };

        if ($signatureField && $timestampField && !empty($data[$signatureField])) {
            $signatureValue = trim($data[$signatureField]);
            if ($signatureValue === '') return;

            $signedAt = now();
            $request->update([$signatureField => $signatureValue, $timestampField => $signedAt]);

            Signature::updateOrCreate(
                ['request_id' => $request->id, 'role' => $user->role],
                ['user_id' => $user->id, 'signature_path' => $signatureValue, 'signed_at' => $signedAt]
            );
        }
    }

    private static function dispatchNotifications(GrantRequest $request, RequestStatus $from, RequestStatus $to): void
    {
        try {
            $url = route('requests.show', $request->id);
            $ref = $request->ref_number;

            match($to) {
                RequestStatus::STAFF1_REVIEWED => self::notifyRole('staff2', $request, 'request_ready_for_review',
                    'Request Ready for Approval',
                    "Request {$ref} has been checked by Staff 1 and is ready for your approval."),

                RequestStatus::STAFF2_APPROVED => self::notifyRole('staff1', $request, 'request_ready_for_print',
                    'Request Approved — Ready for Processing',
                    "Request {$ref} has been approved. Please process and complete it."),

                RequestStatus::RETURNED => self::notifyAdmission($request,
                    'Request Returned for Revision',
                    "Request {$ref} has been returned. Please review the feedback and resubmit."),

                RequestStatus::DECLINED => self::notifyAdmission($request,
                    'Request Declined',
                    "Request {$ref} has been declined. Please check the reason provided."),

                RequestStatus::COMPLETED => self::notifyAdmission($request,
                    'Request Completed',
                    "Request {$ref} has been completed and processed."),

                RequestStatus::SUBMITTED => self::notifyRole('staff1', $request, 'request_resubmitted',
                    'Request Resubmitted',
                    "Request {$ref} has been resubmitted and is ready for verification."),

                default => null,
            };
        } catch (\Throwable $e) {
            \Log::warning('Workflow notification failed', [
                'request_id' => $request->id,
                'to_status'  => $to->value,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private static function notifyRole(string $role, GrantRequest $request, string $type, string $title, string $message): void
    {
        $users = User::where('role', $role)->where('is_active', true)->get();
        $url   = route('requests.show', $request->id);

        foreach ($users as $user) {
            \App\Models\Notification::createForUser($user->id, $type, $title, $message, $url, ['request_id' => $request->id]);
        }
    }

    private static function isSignatureBlank(string $base64): bool
    {
        $raw   = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $bytes = base64_decode($raw, true);

        if ($bytes === false || $bytes === '') {
            return true;
        }

        try {
            $image = @imagecreatefromstring($bytes);

            if ($image === false) {
                return true;
            }

            $width  = imagesx($image);
            $height = imagesy($image);
            $total  = $width * $height;

            if ($total === 0) {
                imagedestroy($image);
                return true;
            }

            $whiteCount = 0;
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $rgb = imagecolorat($image, $x, $y);
                    $r   = ($rgb >> 16) & 0xFF;
                    $g   = ($rgb >> 8)  & 0xFF;
                    $b   =  $rgb        & 0xFF;
                    if ($r > 245 && $g > 245 && $b > 245) {
                        $whiteCount++;
                    }
                }
            }

            imagedestroy($image);

            return ($whiteCount / $total) >= 0.98;

        } catch (\Throwable) {
            return true;
        }
    }

    private static function notifyAdmission(GrantRequest $request, string $title, string $message): void
    {
        \App\Models\Notification::createForUser(
            $request->user_id,
            'request_updated',
            $title,
            $message,
            route('requests.show', $request->id),
            ['request_id' => $request->id]
        );
    }

}
