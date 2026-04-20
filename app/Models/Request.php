<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'request_type_id', 'ref_number', 'status_id',
        'file_path', 'payload',
        'vot_items', 'total_amount',
        'submitter_staff_id', 'submitter_designation', 'submitter_department',
        'submitter_phone', 'submitter_employee_level',
        'signature_data', 'signed_at', 'submitted_at',
        'staff_notes', 'rejection_reason', 'decline_reason', 'return_reason',
        'revision_count',
        'staff1_signature_data', 'staff1_signed_at',
        'staff2_signature_data', 'staff2_signed_at',
    ];

    protected $guarded = [
        'verified_by', 'verified_at',
        'recommended_by', 'recommended_at',
        'is_override',
    ];

    protected $casts = [
        'payload'        => 'array',
        'vot_items'      => 'array',
        'signed_at'      => 'datetime',
        'submitted_at'   => 'datetime',
        'verified_at'    => 'datetime',
        'recommended_at' => 'datetime',
        'total_amount'   => 'decimal:2',
        'staff1_signed_at' => 'datetime',
        'staff2_signed_at' => 'datetime',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function user()          { return $this->belongsTo(User::class); }
    public function requestType()   { return $this->belongsTo(RequestType::class); }
    public function verifiedBy()    { return $this->belongsTo(User::class, 'verified_by'); }
    public function recommendedBy() { return $this->belongsTo(User::class, 'recommended_by'); }
    public function comments()      { return $this->hasMany(Comment::class); }
    public function auditLogs()     { return $this->hasMany(AuditLog::class); }
    public function documents()     { return $this->hasMany(Document::class); }
    public function templateUsages(){ return $this->hasMany(TemplateUsage::class, 'request_id'); }
    public function signatures()    { return $this->hasMany(Signature::class); }
    public function checklistReviews() { return $this->hasMany(ChecklistReview::class); }

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

    // ==========================================
    // Signature helpers
    // ==========================================

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
            'staff1'    => $this->staff1_signature_data,
            'staff2'    => $this->staff2_signature_data,
            default     => null,
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
            'staff1'    => $this->staff1_signed_at,
            'staff2'    => $this->staff2_signed_at,
            default     => null,
        };
    }

    // ==========================================
    // Status helpers
    // ==========================================

    public function getStatus(): RequestStatus    { return RequestStatus::from($this->status_id); }
    public function statusLabel(): string          { return $this->getStatus()->getLabel(); }
    public function statusClass(): string          { return $this->getStatus()->getColor(); }
    public function isFinal(): bool                { return $this->getStatus()->isFinal(); }
    public function isCompleted(): bool            { return $this->status_id === RequestStatus::COMPLETED->value; }
    public function isDeclined(): bool             { return $this->status_id === RequestStatus::DECLINED->value; }
    public function isReturned(): bool             { return $this->status_id === RequestStatus::RETURNED->value; }
    public function canBeEditedByAdmission(): bool { return $this->getStatus()->canBeEditedByAdmission(); }
    public function canBeResubmittedByUser(): bool { return $this->getStatus()->canBeResubmittedByUser(); }
    public function canBeActionedByStaff1(): bool  { return $this->getStatus()->canBeActionedByStaff1(); }
    public function canBeActionedByStaff2(): bool  { return $this->getStatus()->canBeActionedByStaff2(); }

    public function shouldLockVotItems(): bool
    {
        return in_array($this->status_id, [
            RequestStatus::STAFF1_REVIEWED->value,
            RequestStatus::STAFF2_APPROVED->value,
            RequestStatus::COMPLETED->value,
        ]);
    }

    // ==========================================
    // Scopes
    // ==========================================

    

    public function scopeByStatus($query, RequestStatus $status)
    {
        return $query->where('status_id', $status->value);
    }

    // ==========================================
    // Checklist helpers
    // ==========================================

    public function getChecklistItems()
    {
        return $this->requestType?->checklistItems ?? collect();
    }

    public function getChecklistReviews()
    {
        return $this->checklistReviews()->with('checklistItem')->get();
    }

    public function getCheckitemReviewStatus(int $checklistItemId): ?string
    {
        $review = $this->checklistReviews()
            ->where('checklist_item_id', $checklistItemId)
            ->first();
        
        return $review?->status;
    }

    public function hasAllRequiredItemsChecked(): bool
    {
        $requiredItems = $this->getChecklistItems()->filter(function ($item) {
            return $item->is_required;
        });
        
        foreach ($requiredItems as $item) {
            $status = $this->getCheckitemReviewStatus($item->id);
            if ($status !== 'checked') {
                return false;
            }
        }
        
        return true;
    }

    public function hasAnyFlaggedItems(): bool
    {
        $reviews = $this->getChecklistReviews();
        return $reviews->filter(fn($r) => $r->status === 'flagged')->isNotEmpty();
    }

    public function canBeForwardedToStaff2(): bool
    {
        return $this->hasAllRequiredItemsChecked() && !$this->hasAnyFlaggedItems();
    }

    public function getChecklistProgress(): array
    {
        $items = $this->getChecklistItems();
        $reviews = $this->getChecklistReviews();
        
        $total = $items->count();
        $checked = $reviews->filter(fn($r) => $r->status === 'checked')->count();
        $flagged = $reviews->filter(fn($r) => $r->status === 'flagged')->count();
        $pending = $total - $checked - $flagged;
        
        return [
            'total' => $total,
            'checked' => $checked,
            'flagged' => $flagged,
            'pending' => $pending,
            'percentage' => $total > 0 ? round(($checked / $total) * 100) : 0,
        ];
    }
}

