<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'default_template_id', 'field_schema', 'requires_vot', 'metadata', 'is_active'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'field_schema' => 'array',
        'metadata' => 'array',
        'requires_vot' => 'boolean',
        'is_active' => 'boolean',
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
        return $this->belongsTo(FormTemplate::class, 'default_template_id');
    }

    public function requestTypeTemplates()
    {
        return $this->hasMany(RequestTypeTemplate::class);
    }

    public function workflowPolicy()
    {
        return $this->hasOne(RequestTypeWorkflowPolicy::class);
    }

    public function templates()
    {
        return $this->belongsToMany(FormTemplate::class, 'request_type_templates')
            ->withPivot(['is_default', 'sort_order'])
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function getDefaultTemplate()
    {
        // First try the legacy default_template_id
        if ($this->default_template_id) {
            return $this->defaultTemplate;
        }

        // Then try the new system
        $defaultTemplate = $this->requestTypeTemplates()
            ->with('formTemplate')
            ->where('is_default', true)
            ->first();

        return $defaultTemplate?->formTemplate;
    }
}
