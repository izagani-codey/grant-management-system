<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'request_id',
        'uploaded_by',
        'uploader_role',
        'file_path',
        'original_name',
        'is_template',
    ];

    protected $casts = [
        'is_template' => 'boolean',
    ];

    public function request()    { return $this->belongsTo(Request::class); }
    public function uploader()   { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function isUploadedByStaff2(): bool { return $this->uploader_role === 'staff2'; }
    public function isUploadedByUser(): bool   { return $this->uploader_role === 'user'; }
}
