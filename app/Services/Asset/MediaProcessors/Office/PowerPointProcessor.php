<?php

declare(strict_types=1);

namespace App\Services\Asset\MediaProcessors\Office;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpPresentation\IOFactory;

class PowerPointProcessor extends AbstractOfficeProcessor
{
    public function canProcess(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();

        return in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
        ]);
    }

    protected function getDocumentProperties(UploadedFile $file): \PhpOffice\PhpPresentation\DocumentProperties
    {
        return IOFactory::load($file->getRealPath())->getDocumentProperties();
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        parent::process($file, $metadata); // Call parent to extract common properties

        try {
            $presentation = IOFactory::load($file->getRealPath());
            $metadata['slide_count'] = $presentation->getSlideCount();

        } catch (\Exception $e) {
            Log::error('Failed to process PPTX specific properties: '.$e->getMessage());
        }
    }
}
