<?php

namespace App\Services;

use App\Models\Request as GrantRequest;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RequestPdfService
{
    /**
     * Generate a filled PDF for the given request and store it.
     * Returns the stored file path.
     */
    public static function generate(GrantRequest $request, ?Document $template = null): string
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

        // Build signature slots — from request type metadata if defined,
        // otherwise fall back to the standard 2/3 signature layout.
        $signatureSlots = self::buildSignatureSlots($request, $layout);

        $pdf = Pdf::loadView('pdf-template', [
            'request'        => $request,
            'layout'         => $layout,
            'signatureSlots' => $signatureSlots,
        ])->setPaper('a4', 'portrait');

        $filename = 'requests/pdf/' . $request->ref_number . '_' . now()->format('Ymd_His') . '.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Build the ordered list of signature slots for the PDF.
     *
     * Each slot: ['role' => string, 'label' => string]
     *
     * Source priority:
     *   1. request_types.metadata['signature_roles']  — admin-configured per type
     *   2. Standard fallback based on requires_dean_signature boolean
     *
     * To configure per request type, set metadata like:
     * {
     *   "signature_roles": [
     *     {"role": "applicant", "label": "Student Applicant"},
     *     {"role": "staff2",    "label": "Authorised Officer"},
     *     {"role": "dean",      "label": "Dean / Faculty Approver"}
     *   ]
     * }
     */
    private static function buildSignatureSlots(GrantRequest $request, string $layout): array
    {
        $metadata = $request->requestType->metadata ?? [];
        $configured = $metadata['signature_roles'] ?? null;

        if (!empty($configured) && is_array($configured)) {
            // Validate each entry has at least a 'role' key
            $valid = array_filter($configured, fn($s) => !empty($s['role']));
            if (!empty($valid)) {
                return array_values($valid);
            }
        }

        // Default fallback — always present
        $slots = [
            ['role' => 'applicant', 'label' => 'Applicant'],
            ['role' => 'staff2',    'label' => 'Authorised Officer (Staff 2)'],
        ];

        if ($layout === 'three_signatures') {
            $slots[] = ['role' => 'dean', 'label' => 'Dean / Faculty Approver'];
        }

        return $slots;
    }
}
