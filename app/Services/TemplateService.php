<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Request;
use App\Models\TemplateUsage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateService
{
    /**
     * Generate auto-filled PDF from template
     */
    public static function generateAutoFilledPdf(Document $template, Request $request): string
    {
        // Get data for template filling
        $data = self::prepareTemplateData($template, $request);
        
        // Create temporary filled template path
        $tempPath = 'templates/temp/' . Str::uuid() . '_filled.pdf';
        
        // For now, copy the original template
        // In a real implementation, you would use PDF manipulation library
        // to fill the template fields with the data
        Storage::disk('public')->copy($template->file_path, $tempPath);
        
        // Log template usage
        TemplateUsage::create([
            'template_id' => $template->id,
            'request_id' => $request->id,
            'user_id' => auth()->id(),
            'generated_file_path' => $tempPath,
        ]);
        
        return $tempPath;
    }
    
    /**
     * Prepare data for template filling
     */
    private static function prepareTemplateData(Document $template, Request $request): array
    {
        $data = [];
        $mappings = $template->getMappedFields();
        
        foreach ($mappings as $templateField => $dataSource) {
            $data[$templateField] = self::extractDataValue($dataSource, $request);
        }
        
        return $data;
    }
    
    /**
     * Extract value from data source
     */
    private static function extractDataValue(string $dataSource, Request $request): string
    {
        $parts = explode('.', $dataSource);
        $source = $parts[0];
        $field = $parts[1] ?? null;
        
        return match($source) {
            'user' => self::getUserFieldValue($request->user, $field),
            'request' => self::getRequestFieldValue($request, $field),
            default => '',
        };
    }
    
    /**
     * Get user field value
     */
    private static function getUserFieldValue(User $user, ?string $field): string
    {
        return match($field) {
            'name' => $user->name ?? '',
            'staff_id' => $user->staff_id ?? '',
            'designation' => $user->designation ?? '',
            'department' => $user->department ?? '',
            'phone' => $user->phone ?? '',
            'email' => $user->email ?? '',
            'employee_level' => $user->employee_level ?? '',
            'signature_data' => $user->signature_data ?? '',
            default => '',
        };
    }
    
    /**
     * Get request field value
     */
    private static function getRequestFieldValue(Request $request, ?string $field): string
    {
        return match($field) {
            'ref_number' => $request->ref_number ?? '',
            'request_type' => $request->requestType->name ?? '',
            'total_amount' => number_format($request->total_amount ?? 0, 2),
            'description' => $request->payload['description'] ?? '',
            'submitted_at' => $request->submitted_at?->format('d M Y') ?? '',
            default => '',
        };
    }
    
    /**
     * Get available templates for request type
     */
    public static function getAvailableTemplates(?string $requestType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Document::where('document_type', 'template')->where('is_active', true);
        
        if ($requestType) {
            $query->where('request_type_id', $requestType);
        }
        
        return $query->latest()->get();
    }
    
    /**
     * Validate template field mappings
     */
    public static function validateFieldMappings(array $mappings): array
    {
        $errors = [];
        $availableFields = (new Document())->getAvailableFields();
        
        foreach ($mappings as $templateField => $dataSource) {
            if (!isset($availableFields[$dataSource])) {
                $errors[$templateField] = "Invalid data source: {$dataSource}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Get template usage statistics
     */
    public static function getTemplateStats(): array
    {
        $totalTemplates = Document::where('document_type', 'template')->count();
        $activeTemplates = Document::where('document_type', 'template')->where('is_active', true)->count();
        $totalUsage = TemplateUsage::count();
        $recentUsage = TemplateUsage::where('created_at', '>=', now()->subDays(30))->count();
        
        return [
            'total_templates' => $totalTemplates,
            'active_templates' => $activeTemplates,
            'total_usage' => $totalUsage,
            'recent_usage' => $recentUsage,
        ];
    }
}
