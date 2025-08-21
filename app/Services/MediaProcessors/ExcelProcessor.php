<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ExcelProcessor extends AbstractOfficeProcessor
{
    public function canProcess(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ]);
    }

    protected function getDocumentProperties(UploadedFile $file)
    {
        return IOFactory::load($file->getRealPath())->getProperties();
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        parent::process($file, $metadata); // Call parent to extract common properties

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $metadata['sheet_count'] = $spreadsheet->getSheetCount();

        } catch (\Exception $e) {
            Log::error('Failed to process XLSX specific properties: ' . $e->getMessage());
        }
    }
}