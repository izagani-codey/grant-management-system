<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfInfoService
{
    public function getPageCount(string $storagePath): int
    {
        try {
            $pdf   = new Fpdi();
            $count = $pdf->setSourceFile(Storage::path($storagePath));
            return (int) $count;
        } catch (\Throwable) {
            return 1;
        }
    }

    public function getPageDimensions(string $storagePath, int $page = 1): array
    {
        try {
            $pdf = new Fpdi();
            $pdf->setSourceFile(Storage::path($storagePath));
            $tpl  = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);
            return [
                'width'  => (float) $size['width'],
                'height' => (float) $size['height'],
            ];
        } catch (\Throwable) {
            return ['width' => 210.0, 'height' => 297.0];
        }
    }
}
