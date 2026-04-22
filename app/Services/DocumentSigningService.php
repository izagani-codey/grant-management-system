<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Request as GrantRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class DocumentSigningService
{
    /**
     * Stamp applicant + staff2 signatures onto the user's uploaded PDF.
     * Runs after STAFF2_APPROVED transition. Returns the signed Document or null on any failure.
     */
    public function stampAndStore(GrantRequest $request): ?Document
    {
        if (!$request->requestType?->requires_signature) {
            return null;
        }

        $request->loadMissing(['documents', 'signatures', 'requestType']);

        // Find first PDF uploaded by the applicant for this request
        $userPdf = $request->documents
            ->where('document_type', DocumentType::UserSubmission)
            ->first(fn(Document $d) => $d->isPdf());

        if (!$userPdf) {
            Log::info('DocumentSigningService: no user PDF found', ['request_id' => $request->id]);
            return null;
        }

        // Find template for this request type that has at least signature_zones or field_zones configured
        $template = Document::where('request_type_id', $request->request_type_id)
            ->where('document_type', DocumentType::Template->value)
            ->where(fn($q) => $q->whereNotNull('signature_zones')->orWhereNotNull('field_zones'))
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$template) {
            Log::info('DocumentSigningService: no template with zones configured', ['request_id' => $request->id]);
            return null;
        }

        $zones        = $template->signature_zones ?? [];
        $applicantSig = $request->getSignatureImageForRole('applicant');
        $staff2Sig    = $request->getSignatureImageForRole('staff2');

        $tmpApplicant = $applicantSig ? $this->base64ToTempFile($applicantSig) : null;
        $tmpStaff2    = $staff2Sig    ? $this->base64ToTempFile($staff2Sig)    : null;

        $sourcePath = Storage::disk('public')->path($userPdf->file_path);

        try {
            $pdf = new Fpdi('P', 'mm');
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl  = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                if ($tmpApplicant && isset($zones['applicant']) && (int) $zones['applicant']['page'] === $pageNo) {
                    $z = $zones['applicant'];
                    $pdf->Image($tmpApplicant, (float) $z['x'], (float) $z['y'], (float) $z['width'], (float) $z['height'], 'PNG');
                }

                if ($tmpStaff2 && isset($zones['staff2']) && (int) $zones['staff2']['page'] === $pageNo) {
                    $z = $zones['staff2'];
                    $pdf->Image($tmpStaff2, (float) $z['x'], (float) $z['y'], (float) $z['width'], (float) $z['height'], 'PNG');
                }

                // Stamp field values as text
                foreach ($template->field_zones ?? [] as $fieldName => $z) {
                    if ((int) $z['page'] !== $pageNo) continue;
                    $value = (string) ($request->field_values[$fieldName] ?? '');
                    if ($value === '') continue;
                    $pdf->SetFont('Helvetica', '', (float) ($z['font_size'] ?? 10));
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetXY((float) $z['x'], (float) $z['y']);
                    $pdf->MultiCell((float) $z['width'], (float) $z['height'], $value, 0, 'L');
                }
            }

            $filename    = 'signed_' . $request->ref_number . '_' . time() . '.pdf';
            $storagePath = "documents/request-{$request->id}/signed/{$filename}";
            Storage::disk('public')->put($storagePath, $pdf->Output('S'));

            $signedDoc = Document::create([
                'request_id'      => $request->id,
                'request_type_id' => $request->request_type_id,
                'uploaded_by'     => $request->recommended_by ?? $request->user_id,
                'uploader_role'   => 'system',
                'file_path'       => $storagePath,
                'original_name'   => $filename,
                'document_type'   => DocumentType::SignedDocument->value,
            ]);

            $request->update(['signed_document_id' => $signedDoc->id]);

            Log::info('DocumentSigningService: signed PDF created', [
                'request_id'      => $request->id,
                'signed_document' => $signedDoc->id,
            ]);

            return $signedDoc;

        } catch (\Exception $e) {
            Log::warning('DocumentSigningService: failed to stamp PDF', [
                'request_id' => $request->id,
                'error'      => $e->getMessage(),
            ]);
            return null;
        } finally {
            $this->cleanupTempFiles($tmpApplicant, $tmpStaff2);
        }
    }

    private function base64ToTempFile(string $base64): string
    {
        $data = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $path = sys_get_temp_dir() . '/' . uniqid('sig_', true) . '.png';
        file_put_contents($path, base64_decode($data));
        return $path;
    }

    private function cleanupTempFiles(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
