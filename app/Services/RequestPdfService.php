<?php

namespace App\Services;

use App\Models\Request as GrantRequest;
use App\Models\FormTemplate;
use App\Models\TemplateUsage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class RequestPdfService
{
    /**
     * Generate a filled PDF for the given request and store it.
     * Returns the stored file path.
     */
    public static function generate(GrantRequest $request, ?FormTemplate $template = null): string
    {
        $request->loadMissing([
            'user',
            'requestType.workflowPolicy',
            'verifiedBy',
            'recommendedBy',
            'deanApprovedBy',
            'signatures',
        ]);

        // Prepare template data for view
        $templateData = null;
        $backgroundImage = null;
        
        if ($template) {
            // If template is an image, prepare as background
            if (in_array(strtolower(pathinfo($template->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])) {
                $backgroundImage = Storage::disk('public')->url($template->file_path);
            }
            
            $templateData = [
                'template' => $template,
                'background_image' => $backgroundImage,
            ];
            
            // Log template usage
            try {
                TemplateUsage::create([
                    'template_id' => $template->id,
                    'request_id' => $request->id,
                    'user_id' => $request->user_id,
                    'generated_file_path' => null, // Will be updated after generation
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail PDF generation
                \Log::warning('Template usage logging failed', [
                    'template_id' => $template->id,
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pdf = Pdf::loadView('pdf-template', array_merge([
            'request' => $request,
            'layout' => $request->requiresDeanSignature() ? 'three_signatures' : 'two_signatures',
        ], $templateData ?? []))->setPaper('a4', 'portrait');

        $filename = 'requests/pdf/' . $request->ref_number . '_' . now()->format('Ymd_His') . '.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        // Update template usage with generated file path
        if ($template) {
            try {
                TemplateUsage::where('template_id', $template->id)
                    ->where('request_id', $request->id)
                    ->where('user_id', $request->user_id)
                    ->update(['generated_file_path' => $filename]);
            } catch (\Exception $e) {
                \Log::warning('Template usage update failed', [
                    'template_id' => $template->id,
                    'request_id' => $request->id,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $filename;
    }
}
