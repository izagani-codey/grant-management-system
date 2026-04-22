<?php

namespace App\Models;

use App\Models\Request as GrantRequest;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'user_id',
        'content',
        'is_internal',
        'comment_type',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $comment) {
            if (empty($comment->created_at)) {
                $comment->created_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function request()
    {
        return $this->belongsTo(GrantRequest::class);
    }

    // ==========================================
    // Comment Type Helpers
    // ==========================================

    public function isStaff1Comment(): bool
    {
        return $this->comment_type === 'staff1';
    }

    public function isStaff2Comment(): bool
    {
        return $this->comment_type === 'staff2';
    }

    public function isInternalComment(): bool
    {
        return $this->comment_type === 'internal';
    }

    public function getTypeLabel(): string
    {
        return match ($this->comment_type) {
            'staff1' => 'Staff 1 Note',
            'staff2' => 'Staff 2 Note',
            'internal' => 'Internal',
            default => 'Comment'
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->comment_type) {
            'staff1' => 'blue',
            'staff2' => 'purple',
            'internal' => 'gray',
            default => 'gray'
        };
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($this->isStaff1Comment()) {
            return in_array($user->role, ['staff1', 'staff2', 'admin']);
        }

        if ($this->isStaff2Comment()) {
            return in_array($user->role, ['staff2', 'admin']);
        }

        if ($this->isInternalComment()) {
            return in_array($user->role, ['staff1', 'staff2', 'admin']);
        }

        return false;
    }
}

