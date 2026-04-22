<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;
    protected $fillable = [
        'request_id',
        'request_type_id',
        'uploaded_by',
        'uploader_role',
        'file_path',
        'original_name',
        'document_type',
        'is_template',
        'name',
        'description',
        'is_active',
        'download_count',
        'signature_zones',
        'field_zones',
    ];

    protected $casts = [
        'is_template'     => 'boolean',
        'is_active'       => 'boolean',
        'download_count'  => 'integer',
        'document_type'   => DocumentType::class,
        'signature_zones' => 'array',
        'field_zones'     => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'download_count' => 0,
    ];

    public function request()    { return $this->belongsTo(Request::class); }
    public function requestType() { return $this->belongsTo(RequestType::class); }
    public function uploader()   { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function isUploadedByStaff2(): bool { return $this->uploader_role === 'staff2'; }
    public function isUploadedByUser(): bool   { return $this->uploader_role === 'user'; }

    // Template helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getFieldTypeLabel(): string
    {
        return match($this->document_type) {
            DocumentType::Template        => 'Template',
            DocumentType::UserSubmission  => 'User Submission',
            DocumentType::StaffAttachment => 'Staff Attachment',
            DocumentType::SignedDocument  => 'Signed Document',
            default                       => 'Document',
        };
    }

    public function getFileNameAttribute(): string
    {
        return $this->original_name;
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    // Scope for templates
    public function scopeTemplates($query)
    {
        return $query->where('document_type', DocumentType::Template->value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeActiveTemplates($query)
    {
        return $query->where('document_type', DocumentType::Template->value)->where('is_active', true);
    }

    public function scopeUserSubmissions($query)
    {
        return $query->where('document_type', DocumentType::UserSubmission->value);
    }

    public function scopeStaffAttachments($query)
    {
        return $query->where('document_type', DocumentType::StaffAttachment->value);
    }

    // Template-specific helper methods
    public function isTemplate(): bool
    {
        return $this->document_type === DocumentType::Template;
    }

    public function isUserSubmission(): bool
    {
        return $this->document_type === DocumentType::UserSubmission;
    }

    public function isStaffAttachment(): bool
    {
        return $this->document_type === DocumentType::StaffAttachment;
    }

    public function getStoragePath(): string
    {
        return $this->file_path;
    }

    public function getPublicUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getDownloadUrl(): string
    {
        return route('documents.download', $this->id);
    }

    public function getFileExtension(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    public function isPdf(): bool
    {
        return strtolower($this->getFileExtension()) === 'pdf';
    }

    public function isImage(): bool
    {
        return in_array(strtolower($this->getFileExtension()), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    public function isWordDocument(): bool
    {
        return in_array(strtolower($this->getFileExtension()), ['doc', 'docx']);
    }

    public function isExcelDocument(): bool
    {
        return in_array(strtolower($this->getFileExtension()), ['xls', 'xlsx']);
    }

    public function getFileIcon(): string
    {
        if ($this->isPdf()) {
            return 'M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z';
        } elseif ($this->isImage()) {
            return 'M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z';
        } elseif ($this->isWordDocument()) {
            return 'M4 4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-5L9 2H4z';
        } elseif ($this->isExcelDocument()) {
            return 'M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z';
        } else {
            return 'M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.586 5L14 2.414A2 2 0 0012.586 2H9z';
        }
    }

    public function getFileColor(): string
    {
        if ($this->isPdf()) return 'text-red-500';
        if ($this->isImage()) return 'text-blue-500';
        if ($this->isWordDocument()) return 'text-blue-600';
        if ($this->isExcelDocument()) return 'text-green-600';
        return 'text-gray-500';
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->getFileSize();
        if ($bytes === 0) return '0 Bytes';
        
        $units = ['Bytes', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    private function getFileSize(): int
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->size($this->file_path);
        }
        return 0;
    }

    // Template management methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function toggle(): void
    {
        $this->update(['is_active' => !$this->is_active]);
    }

    // Usage tracking
    public function getUsageCount(): int
    {
        // This would be implemented when we add template usage tracking
        return 0;
    }

    public function getLastUsedAt(): ?\Carbon\Carbon
    {
        // This would be implemented when we add template usage tracking
        return null;
    }

    /**
     * Get available fields for template mapping
     */
    public function getAvailableFields(): array
    {
        return [
            'name' => 'Document Name',
            'description' => 'Description',
            'document_type' => 'Document Type',
            'is_active' => 'Active Status',
            'uploaded_by' => 'Uploader ID',
            'uploader_role' => 'Uploader Role',
            'request_type_id' => 'Request Type ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
