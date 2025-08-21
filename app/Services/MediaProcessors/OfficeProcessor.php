<?php

namespace App\Services\MediaProcessors;

use App\Services\MediaProcessors\Office\ExcelProcessor;
use App\Services\MediaProcessors\Office\PowerPointProcessor;
use App\Services\MediaProcessors\Office\WordDocumentProcessor;
use Illuminate\Http\UploadedFile;

class OfficeProcessor implements MediaProcessorInterface
{
    private array $processors;

    public function __construct(
        ExcelProcessor $excelProcessor,
        PowerPointProcessor $powerPointProcessor,
        WordDocumentProcessor $wordDocumentProcessor
    ) {
        $this->processors = [
            'excel' => $excelProcessor,
            'powerpoint' => $powerPointProcessor,
            'word' => $wordDocumentProcessor,
        ];
    }

    public function canProcess(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, [
            // Excel
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            // PowerPoint
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
            // Word
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/zip', // Sometimes DOCX files are identified as zip
        ]);
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        $mimeType = $file->getMimeType();

        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ])) {
            $this->processors['excel']->process($file, $metadata);
        } elseif (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
        ])) {
            $this->processors['powerpoint']->process($file, $metadata);
        } elseif (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/zip', // Sometimes DOCX files are identified as zip
        ])) {
            $this->processors['word']->process($file, $metadata);
        }
    }
}
