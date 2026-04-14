<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'user_id', 'request_type_id', 'ref_number', 'status_id',
        'snapshot_requires_dean_signature',
        'file_path', 'payload',
        'vot_items', 'total_amount',
        'submitter_staff_id', 'submitter_designation', 'submitter_department',
        'submitter_phone', 'submitter_employee_level',
        'signature_data', 'signed_at', 'submitted_at',
        'staff_notes', 'rejection_reason',
        'revision_count', 'deadline', 'is_priority',
        'staff1_signature_data', 'staff1_signed_at',
        'staff2_signature_data', 'staff2_signed_at',
        'dean_signature_data', 'dean_signed_at',
    ];

    protected $guarded = [
        'verified_by', 'verified_at', 'recommended_by', 'recommended_at',
        'dean_approved_by', 'dean_approved_at', 'dean_notes', 'dean_rejection_reason',
        'is_override',
    ];

    protected $casts = [
        'payload'     => 'array',
        'vot_items'   => 'array',
        'is_priority' => 'boolean',
        'snapshot_requires_dean_signature' => 'boolean',
        'deadline'    => 'date',
        'signed_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'dean_approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'recommended_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'staff1_signed_at' => 'datetime',
        'staff2_signed_at' => 'datetime',
        'dean_signed_at' => 'datetime',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function user()         { return $this->belongsTo(User::class); }
    public function requestType()  { return $this->belongsTo(RequestType::class); }
    public function verifiedBy()   { return $this->belongsTo(User::class, 'verified_by'); }
    public function recommendedBy(){ return $this->belongsTo(User::class, 'recommended_by'); }
    public function deanApprovedBy(){ return $this->belongsTo(User::class, 'dean_approved_by'); }
    public function comments()     { return $this->hasMany(Comment::class); }
    public function auditLogs()    { return $this->hasMany(AuditLog::class); }
    public function documents()    { return $this->hasMany(Document::class); }
    public function templateUsages(){ return $this->hasMany(TemplateUsage::class, 'request_id'); }
    public function signatures()   { return $this->hasMany(Signature::class); }

    // ==========================================
    // VOT helpers
    // ==========================================

    public function getVotItems(): array
    {
        return $this->vot_items ?? [];
    }

    public function computedTotal(): float
    {
        return collect($this->getVotItems())->sum(fn($item) => (float) ($item['amount'] ?? 0));
    }

    public function hasSignature(): bool
    {
        return !empty($this->signature_data);
    }

    public function getSignatureForRole(string $role): ?Signature
    {
        if ($this->relationLoaded('signatures')) {
            return $this->signatures->firstWhere('role', $role);
        }

        return $this->signatures()->where('role', $role)->first();
    }

    public function getSignatureImageForRole(string $role): ?string
    {
        $normalized = $this->getSignatureForRole($role)?->signature_path;
        if (!empty($normalized)) {
            return $normalized;
        }

        return match ($role) {
            'applicant' => $this->signature_data,
            'staff2' => $this->staff2_signature_data,
            'dean' => $this->dean_signature_data,
            default => null,
        };
    }

    public function getSignedAtForRole(string $role): ?\Illuminate\Support\Carbon
    {
        $normalized = $this->getSignatureForRole($role)?->signed_at;
        if ($normalized) {
            return $normalized;
        }

        return match ($role) {
            'applicant' => $this->signed_at,
            'staff2' => $this->staff2_signed_at,
            'dean' => $this->dean_signed_at,
            default => null,
        };
    }

    // ==========================================
    // Status helpers
    // ==========================================

    public function getStatus(): RequestStatus       { return RequestStatus::from($this->status_id); }
    public function statusLabel(): string             { return $this->getStatus()->getLabel(); }
    public function statusClass(): string             { return $this->getStatus()->getColor(); }
    public function isFinal(): bool                   { return $this->isTrulyComplete() || $this->status_id === RequestStatus::REJECTED->value; }
    public function canBeEditedByAdmission(): bool    { return $this->getStatus()->canBeEditedByAdmission(); }
    public function canBeActionedByStaff1(): bool     { return $this->getStatus()->canBeActionedByStaff1(); }
    public function canBeActionedByStaff2(): bool     { return $this->getStatus()->canBeActionedByStaff2(); }
    public function canBeActionedByDean(): bool       { return $this->status_id === RequestStatus::STAFF2_APPROVED->value && $this->requiresDeanSignature(); }

    public function isTrulyComplete(): bool
    {
        return $this->status_id === RequestStatus::DEAN_APPROVED->value
            || ($this->status_id === RequestStatus::STAFF2_APPROVED->value && !$this->requiresDeanSignature());
    }

    public function requiresDeanSignature(): bool
    {
        if ($this->snapshot_requires_dean_signature !== null) {
            return $this->snapshot_requires_dean_signature;
        }

        return $this->requestType?->workflowPolicy?->requires_dean_signature ?? true;
    }

    // ==========================================
    // Priority / deadline helpers
    // ==========================================

    public function isUrgent(): bool
    {
        if (!$this->deadline) return false;
        $days = now()->diffInDays($this->deadline, false);
        return $days >= 0 && $days <= 3;
    }

    public function priorityLabel(): string
    {
        if ($this->isUrgent())   return 'URGENT ⚠️';
        if ($this->is_priority)  return 'HIGH PRIORITY';
        if ($this->isAutoHighPriority()) return 'HIGH PRIORITY (Auto)';
        return 'NORMAL';
    }

    public function priorityBadgeClass(): string
    {
        if ($this->isUrgent())  return 'bg-red-500 text-white';
        if ($this->is_priority) return 'bg-orange-500 text-white';
        if ($this->isAutoHighPriority()) return 'bg-yellow-500 text-white';
        return 'bg-green-500 text-white';
    }

    public function isAutoHighPriority(): bool
    {
        if (!$this->deadline) return false;
        $days = now()->diffInDays($this->deadline,false);
        return $days > 3 && $days <= 5;
    }

    public function calculateAutoPriority(): void
    {
        if (!$this->deadline) return;
        
        $daysUntil = $this->daysUntilDeadline();
        if ($daysUntil !== null && $daysUntil <= 5) {
            $this->is_priority = true;
            $this->save();
        }
    }

    public function updatePriorityFromDeadline(): void
    {
        if (!$this->deadline) return;
        
        $daysUntil = $this->daysUntilDeadline();
        $shouldBeHighPriority = $daysUntil !== null && $daysUntil <= 5;
        
        // Only auto-update if not manually set (we could add a is_manual_priority flag)
        if ($shouldBeHighPriority && !$this->is_priority) {
            $this->is_priority = true;
            $this->save();
        } elseif (!$shouldBeHighPriority && $this->isAutoHighPriority()) {
            $this->is_priority = false;
            $this->save();
        }
    }

    public function daysUntilDeadline(): ?int
    {
        if (!$this->deadline) return null;
        return (int) now()->diffInDays($this->deadline, false);
    }

    public function shouldLockVotItems(): bool
    {
        // Lock VOT items if request has been verified by staff or is further in workflow
        return in_array($this->status_id, [
            RequestStatus::STAFF1_APPROVED->value,
            RequestStatus::STAFF2_APPROVED->value,
            RequestStatus::DEAN_APPROVED->value,
            RequestStatus::REJECTED->value,
        ]);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeByPriority($query, $isPriority) { return $query->where('is_priority', $isPriority); }
    public function scopeUrgent($query)
    {
        return $query->whereBetween('deadline', [now(), now()->addDays(3)])
            ->notTrulyComplete()
            ->orderBy('deadline', 'asc');
    }

    public function scopeTrulyApproved($query)
    {
        return $query->where(function ($approvedQuery) {
            $approvedQuery
                ->where('status_id', RequestStatus::DEAN_APPROVED->value)
                ->orWhere(function ($staff2ApprovedQuery) {
                    $staff2ApprovedQuery
                        ->where('status_id', RequestStatus::STAFF2_APPROVED->value)
                        ->where('snapshot_requires_dean_signature', false);
                });
        });
    }

    public function scopeNotTrulyComplete($query)
    {
        return $query
            ->where('status_id', '!=', RequestStatus::REJECTED->value)
            ->where(function ($activeQuery) {
                $activeQuery
                    ->where('status_id', '!=', RequestStatus::DEAN_APPROVED->value)
                    ->where(function ($staff2Query) {
                        $staff2Query
                            ->where('status_id', '!=', RequestStatus::STAFF2_APPROVED->value)
                            ->orWhere('snapshot_requires_dean_signature', true)
                            ->orWhereNull('snapshot_requires_dean_signature');
                    });
            });
    }

    public function scopePendingDeanReview($query)
    {
        return $query
            ->where('status_id', RequestStatus::STAFF2_APPROVED->value)
            ->where(function ($pendingQuery) {
                $pendingQuery
                    ->where('snapshot_requires_dean_signature', true)
                    ->orWhereNull('snapshot_requires_dean_signature');
            });
    }
    public function scopeByStatus($query, RequestStatus $status) { return $query->where('status_id', $status->value); }
}
