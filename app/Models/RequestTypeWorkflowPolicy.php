<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestTypeWorkflowPolicy extends Model
{
    protected $fillable = [
        'request_type_id',
        'requires_dean_signature',
    ];

    protected $casts = [
        'requires_dean_signature' => 'boolean',
    ];

    public function requestType()
    {
        return $this->belongsTo(RequestType::class);
    }

    public function getSignatureLayoutAttribute(): string
    {
        return $this->requires_dean_signature ? 'three_signatures' : 'two_signatures';
    }
}
