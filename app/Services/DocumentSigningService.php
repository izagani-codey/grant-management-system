<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Request as GrantRequest;
use App\Traits\ResolvesPresetZones;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use setasign\Fpdi\Fpdi;

class DocumentSigningService
{
    use ResolvesPresetZones;

    /**
     * Stamp applicant + staff2 signatures (and field values) onto the user's uploaded PDF.
     * Runs after STAFF2_APPROVED transition. Returns the signed Document or null on any failure.
     *
     * Stamping strategy:
     *  - New path: uses template->zones (normalized nx/ny/nw/nh ratios keyed by page index 0-based)
     *  - Legacy fallback: uses template->signature_zones + template->field_zones (absolute mm coords)
     */
    public function stampAndStore(GrantRequest $request): ?Document
    {
        if (!$request->requestType?->requires_signature) {
            return null;
        }

        $request->load(['documents', 'signatures', 'requestType']);

        $userPdf = $request->documents
            ->where('document_type', DocumentType::UserSubmission)
            ->first(fn(Document $d) => $d->isPdf());

        if (!$userPdf) {
            $userXls = $request->documents
                ->where('document_type', DocumentType::UserSubmission)
                ->first(fn(Document $d) => $d->isExcelDocument());

            if (!$userXls) {
                Log::info('DocumentSigningService: no user PDF or XLS found', [
                    'request_id' => $request->id,
                ]);
                return null;
            }

            return $this->stampAndStoreXls($request, $userXls);
        }

        $template = Document::where('request_type_id', $request->request_type_id)
            ->where('document_type', DocumentType::Template->value)
            ->where(fn($q) => $q
                ->whereNotNull('zones')
                ->orWhereNotNull('signature_zones')
                ->orWhereNotNull('field_zones')
            )
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$template) {
            Log::info('DocumentSigningService: no template with zones configured', ['request_id' => $request->id]);
            return null;
        }

        $applicantSig = $request->getSignatureImageForRole('applicant');
        $staff2Sig    = $request->getSignatureImageForRole('staff2');

        $tmpApplicant = $applicantSig ? $this->base64ToTempFile($applicantSig) : null;
        $tmpStaff2    = $staff2Sig    ? $this->base64ToTempFile($staff2Sig)    : null;

        $sourcePath = Storage::disk('public')->path($userPdf->file_path);

        try {
            $pdf = new Fpdi('P', 'mm');
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($sourcePath);

            $pdfInfo = app(PdfInfoService::class);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl  = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                $pageW = (float) $size['width'];
                $pageH = (float) $size['height'];

                // ── New zones path (normalized ratios) ──────────────────────────
                if (!empty($template->zones)) {
                    $pageIndex = $pageNo - 1;
                    $pageZones = $template->zones[$pageIndex] ?? $template->zones[(string) $pageIndex] ?? [];

                    foreach ($pageZones as $zone) {
                        $x = (float) $zone['nx'] * $pageW;
                        $y = (float) $zone['ny'] * $pageH;
                        $w = (float) $zone['nw'] * $pageW;
                        $h = (float) $zone['nh'] * $pageH;

                        $tool = $zone['tool'] ?? '';

                        if (str_starts_with($tool, 'preset_')) {
                            // Handle preset zones with resolved values
                            $value = $this->resolvePresetValue($tool, $request);
                            if ($value !== '') {
                                $fontSize = max(8, $h * 2.8);
                                $pdf->SetFont('Helvetica', '', $fontSize);
                                $pdf->SetTextColor(30, 30, 30);
                                $pdf->SetXY($x, $y);
                                $pdf->Cell($w, $h, $value, 0, 0, 'L');
                            }
                        } elseif ($tool === 'applicant_signature' && $tmpApplicant) {
                            $pdf->Image($tmpApplicant, $x, $y, $w, $h, 'PNG');
                        } elseif ($tool === 'staff2_signature' && $tmpStaff2) {
                            $pdf->Image($tmpStaff2, $x, $y, $w, $h, 'PNG');
                        } elseif (str_starts_with($tool, 'field_')) {
                            $fieldName = substr($tool, 6);
                            $value     = (string) ($request->field_values[$fieldName] ?? '');
                            if ($value === '') continue;
                            $fontSize = max(8, $h * 2.8);
                            $pdf->SetFont('Helvetica', '', $fontSize);
                            $pdf->SetTextColor(30, 30, 30);
                            $pdf->SetXY($x, $y);
                            $pdf->Cell($w, $h, $value, 0, 0, 'L');
                        }
                    }

                    continue; // skip legacy path for this page
                }

                // ── Legacy fallback path (absolute mm coords) ────────────────────
                $legacySigZones = $template->signature_zones ?? [];

                if ($tmpApplicant && isset($legacySigZones['applicant'])
                    && (int) $legacySigZones['applicant']['page'] === $pageNo) {
                    $z = $legacySigZones['applicant'];
                    $pdf->Image($tmpApplicant, (float) $z['x'], (float) $z['y'], (float) $z['width'], (float) $z['height'], 'PNG');
                }

                if ($tmpStaff2 && isset($legacySigZones['staff2'])
                    && (int) $legacySigZones['staff2']['page'] === $pageNo) {
                    $z = $legacySigZones['staff2'];
                    $pdf->Image($tmpStaff2, (float) $z['x'], (float) $z['y'], (float) $z['width'], (float) $z['height'], 'PNG');
                }

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
            Storage::disk('local')->put($storagePath, $pdf->Output('S'));

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

        } catch (\Throwable $e) {
            Log::warning('DocumentSigningService: failed to stamp PDF', [
                'request_id' => $request->id,
                'error'      => $e->getMessage(),
            ]);
            return null;
        } finally {
            $this->cleanupTempFiles($tmpApplicant, $tmpStaff2);
        }
    }

    private function stampAndStoreXls(
        GrantRequest $request,
        Document $userXls
    ): ?Document {
        $template = Document::where('request_type_id', $request->request_type_id)
            ->where('document_type', DocumentType::Template->value)
            ->where(fn($q) => $q->whereNotNull('zones')->orWhereNotNull('signature_zones'))
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$template) {
            Log::info('DocumentSigningService: no XLS template with zones configured', [
                'request_id' => $request->id,
            ]);
            return null;
        }

        $applicantSig = $request->getSignatureImageForRole('applicant');
        $staff2Sig    = $request->getSignatureImageForRole('staff2');

        try {
            $sourcePath  = Storage::disk('public')->path($userXls->file_path);
            $spreadsheet = IOFactory::load($sourcePath);
            $sheet       = $spreadsheet->getActiveSheet();

            if (!empty($template->zones)) {
                // ── New zones format (visual zone designer) ──────────────
                // Flatten all page arrays into a single list.
                $allZones = [];
                foreach ($template->zones as $pageZones) {
                    if (is_array($pageZones)) {
                        $allZones = array_merge($allZones, $pageZones);
                    }
                }

                foreach ($allZones as $zone) {
                    $tool = $zone['tool'] ?? '';

                    if (str_starts_with($tool, 'preset_')) {
                        // Handle preset zones with resolved values
                        $value = $this->resolvePresetValue($tool, $request);
                        if ($value !== '') {
                            $fontSize = max(8, 20); // Default font size for preset text
                            $sheet->setCellValue($zone['cell_start'], $value);
                            $sheet->getStyle($zone['cell_start'])
                                ->getFont()->setSize($fontSize);
                        }
                    } elseif ($tool === 'applicant_signature' && $applicantSig) {
                        $this->placeXlsSignature($sheet, $applicantSig, $zone);
                    } elseif ($tool === 'staff2_signature' && $staff2Sig) {
                        $this->placeXlsSignature($sheet, $staff2Sig, $zone);
                    } elseif (str_starts_with($tool, 'field_') && !empty($zone['cell_start'])) {
                        $fieldName = substr($tool, 6);
                        $value     = (string) ($request->field_values[$fieldName] ?? '');
                        if ($value !== '') {
                            $sheet->setCellValue($zone['cell_start'], $value);
                        }
                    }
                }
            } else {
                // ── Legacy signature_zones (manual mm coordinates) ────────
                $sigZones = $template->signature_zones ?? [];
                $mmToPx   = 3.7795;

                foreach ([
                    'applicant' => $applicantSig,
                    'staff2'    => $staff2Sig,
                ] as $role => $sigBase64) {
                    if (empty($sigBase64) || empty($sigZones[$role])) continue;

                    $z = $sigZones[$role];
                    $this->placeXlsSignature($sheet, $sigBase64, [
                        'x' => (float) $z['x'] * $mmToPx,
                        'y' => (float) $z['y'] * $mmToPx,
                        'w' => (float) $z['width'] * $mmToPx,
                        'h' => (float) $z['height'] * $mmToPx,
                    ]);
                }
            }

            $filename    = 'signed_' . $request->ref_number . '_' . time() . '.xlsx';
            $storagePath = "documents/request-{$request->id}/signed/{$filename}";
            $fullPath    = Storage::disk('local')->path($storagePath);

            Storage::disk('local')->makeDirectory(
                "documents/request-{$request->id}/signed"
            );

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($fullPath);

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

            Log::info('DocumentSigningService: signed XLSX created', [
                'request_id'      => $request->id,
                'signed_document' => $signedDoc->id,
            ]);

            return $signedDoc;

        } catch (\Throwable $e) {
            Log::warning('DocumentSigningService: XLSX stamp failed', [
                'request_id' => $request->id,
                'error'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function placeXlsSignature($sheet, string $sigBase64, array $zone): void
    {
        $data = base64_decode(
            preg_replace('/^data:image\/\w+;base64,/', '', $sigBase64),
            true
        );
        if (!$data) return;

        $gd = imagecreatefromstring($data);
        if (!$gd) return;

        $drawing = new MemoryDrawing();
        $drawing->setImageResource($gd);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX((int) ($zone['x'] ?? 0));
        $drawing->setOffsetY((int) ($zone['y'] ?? 0));
        $drawing->setWidth((int) ($zone['w'] ?? 100));
        $drawing->setHeight((int) ($zone['h'] ?? 30));
        $drawing->setWorksheet($sheet);
    }

    private function base64ToTempFile(string $base64): ?string
    {
        $data    = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $decoded = base64_decode($data, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }
        $path = sys_get_temp_dir() . '/' . uniqid('sig_', true) . '.png';
        file_put_contents($path, $decoded);
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
