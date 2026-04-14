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

        $layout = $request->requiresDeanSignature() ? 'three_signatures' : 'two_signatures';

        $pdf = Pdf::loadView('pdf-template', [
            'request' => $request,
            'layout'  => $layout,
        ])->setPaper('a4', 'portrait');

        $filename = 'requests/pdf/' . $request->ref_number . '_' . now()->format('Ymd_His') . '.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }
}
