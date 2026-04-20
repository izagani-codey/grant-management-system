<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateUsage extends Model
{
    protected $table = 'template_usage';
    
    protected $fillable = [
        'template_id',
        'request_id',
        'user_id',
        'generated_file_path',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function template()
    {
        return $this->belongsTo(Document::class, 'template_id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ==========================================
    // Helper methods
    // ==========================================

    public function getGeneratedFileUrl(): string
    {
        return asset('storage/' . $this->generated_file_path);
    }

    public function getFormattedDate(): string
    {
        return $this->created_at->format('d M Y, h:i A');
    }
}
