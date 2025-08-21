<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpPresentation\IOFactory;
use Illuminate\Support\Facades\Log;

class PowerPointProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
        ]);
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $presentation = IOFactory::load($file->getRealPath());
            $properties = $presentation->getDocumentProperties();

            $metadata['title'] = $properties->getTitle();
            $metadata['author'] = $properties->getCreator();
            $metadata['subject'] = $properties->getSubject();
            $metadata['keywords'] = $properties->getKeywords();
            $metadata['description'] = $properties->getDescription();
            $metadata['category'] = $properties->getCategory();
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
            $metadata['created_at'] = $properties->getCreated();
            $metadata['modified_at'] = $properties->getModified();
            $metadata['slide_count'] = $presentation->getSlideCount();

        } catch (\Exception $e) {
            Log::error('Failed to process PPTX file: ' . $e->getMessage());
        }
    }
}
