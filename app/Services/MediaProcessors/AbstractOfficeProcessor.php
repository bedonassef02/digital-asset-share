<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

abstract class AbstractOfficeProcessor implements MediaProcessorInterface
{
    /**
     * Abstract method to get the document properties object from the file.
     * Each concrete processor will implement this based on its specific library.
     *
     * @param UploadedFile $file
     * @return mixed The document properties object (e.g., PhpWord\DocInfo, PhpSpreadsheet\DocumentProperties)
     */
    abstract protected function getDocumentProperties(UploadedFile $file);

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $properties = $this->getDocumentProperties($file);

            $metadata['title'] = $properties->getTitle();
            $metadata['author'] = $properties->getCreator();
            $metadata['subject'] = $properties->getSubject();
            $metadata['keywords'] = $properties->getKeywords();
            $metadata['description'] = $properties->getDescription();
            $metadata['category'] = $properties->getCategory();
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
            $metadata['created_at'] = $properties->getCreated();
            $metadata['modified_at'] = $properties->getModified();

        } catch (\Exception $e) {
            Log::error('Failed to process Office file: ' . $e->getMessage());
        }
    }
}
