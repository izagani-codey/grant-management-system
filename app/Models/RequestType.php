<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'default_template_id', 'field_schema', 'requires_vot', 'requires_signature', 'metadata', 'is_active', 'required_documents'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'field_schema' => 'array',
        'metadata' => 'array',
        'required_documents' => 'array',
        'requires_vot'       => 'boolean',
        'requires_signature' => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function requestsCount()
    {
        return $this->requests()->count();
    }

    public function defaultTemplate()
    {
        return $this->belongsTo(Document::class, 'default_template_id');
    }

    public function templates()
    {
        return $this->hasMany(Document::class)
            ->where('document_type', 'template')
            ->orderBy('created_at');
    }

    public function activeTemplates()
    {
        return $this->templates()->where('is_active', true);
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class)->active()->ordered();
    }

    public function activeChecklistItems()
    {
        return $this->checklistItems()->active();
    }

    public function requiredChecklistItems()
    {
        return $this->checklistItems()->required();
    }

    public function getDefaultTemplate(?string $signatureLayout = null)
    {
        return $this->defaultTemplate;
    }
}
