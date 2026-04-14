<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'url',
        'is_read',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    public static function createForUser($userId, $type, $title, $message, $url = null, $data = null): ?self
    {
        // Deduplication: skip if an identical unread notification was created in the last 5 minutes
        $recentExists = self::where('user_id', $userId)
            ->where('type', $type)
            ->where('title', $title)
            ->where('is_read', false)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->when(isset($data['request_id']), fn ($q) => $q->whereJsonContains('data->request_id', $data['request_id']))
            ->exists();

        if ($recentExists) {
            return null;
        }

        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'data' => $data,
        ]);
    }
}
