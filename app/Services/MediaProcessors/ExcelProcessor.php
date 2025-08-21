<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class ExcelProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ]);
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $properties = $spreadsheet->getProperties();

            $metadata['title'] = $properties->getTitle();
            $metadata['author'] = $properties->getCreator();
            $metadata['subject'] = $properties->getSubject();
            $metadata['keywords'] = $properties->getKeywords();
            $metadata['description'] = $properties->getDescription();
            $metadata['category'] = $properties->getCategory();
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
            $metadata['created_at'] = $properties->getCreated();
            $metadata['modified_at'] = $properties->getModified();
            $metadata['sheet_count'] = $spreadsheet->getSheetCount();

        } catch (\Exception $e) {
            Log::error('Failed to process XLSX file: ' . $e->getMessage());
        }
    }
}
