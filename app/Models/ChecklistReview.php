<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'checklist_item_id',
        'reviewed_by',
        'status',
        'note',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function checklistItem()
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeChecked($query)
    {
        return $query->where('status', 'checked');
    }

    public function scopeFlagged($query)
    {
        return $query->where('status', 'flagged');
    }

    public function isChecked(): bool
    {
        return $this->status === 'checked';
    }

    public function isFlagged(): bool
    {
        return $this->status === 'flagged';
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'checked' => 'Checked',
            'flagged' => 'Flagged',
            default => 'Unknown',
        };
    }

    public function getStatusClass(): string
    {
        return match($this->status) {
            'checked' => 'bg-green-100 text-green-700',
            'flagged' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
