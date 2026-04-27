<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    public static function exportRequests(Collection $requests): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(SettingsService::get('app_name', config('app.name', 'Grant Request System')))
            ->setTitle('Requests Export')
            ->setSubject('Grant Requests');

        // ─── Sheet 1: Summary ───────────────────────────────────────────
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Summary');

        $summaryHeaders = [
            'Reference No.',
            'Submitted Date & Time',
            'Applicant Name',
            'Staff ID',
            'Designation',
            'Department',
            'Phone',
            'Employee Level',
            'Request Type',
            'Description',
            'Total Amount (RM)',
            'Status',
            'Signed At',
            'Verified By',
            'Recommended By',
            'Revisions',
        ];

        self::writeHeaders($summary, $summaryHeaders, 'A', 1, '1F3864');

        $row = 2;
        foreach ($requests as $req) {
            $summary->fromArray([
                $req->ref_number,
                $req->submitted_at?->format('d/m/Y H:i:s') ?? $req->created_at->format('d/m/Y H:i:s'),
                $req->user->name ?? '',
                $req->submitter_staff_id ?? $req->user->staff_id ?? '',
                $req->submitter_designation ?? $req->user->designation ?? '',
                $req->submitter_department ?? $req->user->department ?? '',
                $req->submitter_phone ?? $req->user->phone ?? '',
                $req->submitter_employee_level ?? $req->user->employee_level ?? '',
                $req->requestType->name ?? '',
                $req->payload['description'] ?? '',
                (float) $req->total_amount,
                $req->statusLabel(),
                $req->signed_at?->format('d/m/Y H:i:s') ?? '',
                $req->verifiedBy?->name ?? '',
                $req->recommendedBy?->name ?? '',
                $req->revision_count ?? 0,
            ], null, 'A' . $row);

            // Format amount column (K) as currency
            $summary->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            // Zebra striping
            if ($row % 2 === 0) {
                $summary->getStyle('A' . $row . ':P' . $row)
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0F4FF');
            }

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'P') as $col) {
            $summary->getColumnDimension($col)->setAutoSize(true);
        }

        // Total row
        if ($requests->count() > 0) {
            $totalRow = $row;
            $summary->setCellValue('J' . $totalRow, 'TOTAL');
            $summary->setCellValue('K' . $totalRow, '=SUM(K2:K' . ($row - 1) . ')');
            $summary->getStyle('K' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $summary->getStyle('J' . $totalRow . ':K' . $totalRow)->getFont()->setBold(true);
            $summary->getStyle('J' . $totalRow . ':K' . $totalRow)
                ->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DCE6F1');
        }

        // Freeze top row
        $summary->freezePane('A2');

        // ─── Sheet 2: VOT Detail ─────────────────────────────────────────
        $votSheet = $spreadsheet->createSheet();
        $votSheet->setTitle('VOT Detail');

        $votHeaders = [
            'Reference No.',
            'Applicant Name',
            'Staff ID',
            'Department',
            'Submitted Date & Time',
            'Status',
            'VOT Code',
            'VOT Description',
            'Amount (RM)',
        ];

        self::writeHeaders($votSheet, $votHeaders, 'A', 1, '375623');

        $votRow = 2;
        foreach ($requests as $req) {
            $votItems = $req->getVotItems();

            if (empty($votItems)) {
                // Request with no VOT items — still include in sheet
                $votSheet->fromArray([
                    $req->ref_number,
                    $req->user->name ?? '',
                    $req->submitter_staff_id ?? $req->user->staff_id ?? '',
                    $req->submitter_department ?? $req->user->department ?? '',
                    $req->submitted_at?->format('d/m/Y H:i:s') ?? $req->created_at->format('d/m/Y H:i:s'),
                    $req->statusLabel(),
                    '—',
                    '—',
                    0,
                ], null, 'A' . $votRow);
                $votRow++;
                continue;
            }

            foreach ($votItems as $item) {
                $votSheet->fromArray([
                    $req->ref_number,
                    $req->user->name ?? '',
                    $req->submitter_staff_id ?? $req->user->staff_id ?? '',
                    $req->submitter_department ?? $req->user->department ?? '',
                    $req->submitted_at?->format('d/m/Y H:i:s') ?? $req->created_at->format('d/m/Y H:i:s'),
                    $req->statusLabel(),
                    $item['vot_code'] ?? '',
                    $item['description'] ?? '',
                    (float) ($item['amount'] ?? 0),
                ], null, 'A' . $votRow);

                $votSheet->getStyle('I' . $votRow)->getNumberFormat()->setFormatCode('#,##0.00');

                if ($votRow % 2 === 0) {
                    $votSheet->getStyle('A' . $votRow . ':I' . $votRow)
                        ->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F0FFF4');
                }

                $votRow++;
            }
        }

        // Total row for VOT sheet
        if ($votRow > 2) {
            $votSheet->setCellValue('H' . $votRow, 'TOTAL');
            $votSheet->setCellValue('I' . $votRow, '=SUM(I2:I' . ($votRow - 1) . ')');
            $votSheet->getStyle('I' . $votRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $votSheet->getStyle('H' . $votRow . ':I' . $votRow)->getFont()->setBold(true);
        }

        foreach (range('A', 'I') as $col) {
            $votSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $votSheet->freezePane('A2');

        // Return to summary sheet
        $spreadsheet->setActiveSheetIndex(0);

        // ─── Stream response ─────────────────────────────────────────────
        $filename = 'STRG_Requests_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private static function writeHeaders($sheet, array $headers, string $startCol, int $row, string $bgColor): void
    {
        $col = $startCol;
        foreach ($headers as $header) {
            $cell = $col . $row;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']]],
            ]);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(22);
    }
}
