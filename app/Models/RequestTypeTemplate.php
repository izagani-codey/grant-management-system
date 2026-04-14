<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestTypeTemplate extends Model
{
    protected $fillable = [
        'request_type_id',
        'form_template_id',
        'is_default',
        'sort_order',
        'signature_layout',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function requestType()
    {
        return $this->belongsTo(RequestType::class);
    }

    public function formTemplate()
    {
        return $this->belongsTo(FormTemplate::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeForRequestType($query, $requestTypeId)
    {
        return $query->where('request_type_id', $requestTypeId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeForLayout($query, string $layout)
    {
        return $query->where('signature_layout', $layout);
    }

    // ==========================================
    // Helper methods
    // ==========================================

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }

    public function setAsDefault(): void
    {
        // Remove default flag from other templates for this request type
        static::where('request_type_id', $this->request_type_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        $this->is_default = true;
        $this->save();
    }
}
