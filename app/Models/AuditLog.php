<?php

namespace App\Models;

use App\Models\Request as GrantRequest;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'request_id',
        'actor_id',
        'actor_role',
        'action',
        'from_status',
        'to_status',
        'note',
        'rejection_reason',
        'is_override',
        'signature_data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function request()
    {
        return $this->belongsTo(GrantRequest::class);
    }

    public function getActionLabel(): string
    {
        return match($this->action) {
            'staff1_reviewed' => 'Staff 1 Reviewed',
            'staff2_approved' => 'Staff 2 Approved',
            'returned' => 'Returned for Revision',
            'declined' => 'Declined',
            'override_staff1' => 'Staff 1 Override',
            'resubmitted' => 'Resubmitted',
            'status_changed' => 'Status Changed',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function isOverrideAction(): bool
    {
        return $this->is_override || $this->action === 'override_staff1';
    }

    public function scopeForRequest($query, $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    public function scopeByActor($query, $userId)
    {
        return $query->where('actor_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeOverrides($query)
    {
        return $query->where('is_override', true);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
