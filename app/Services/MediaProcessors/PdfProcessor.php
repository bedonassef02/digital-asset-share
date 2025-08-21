<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class PdfProcessor implements MediaProcessorInterface
{
    public function __construct(
        private ?Parser $pdfParser = null
    ) {
        $this->pdfParser ??= new Parser();
    }

    public function canProcess(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'application/pdf';
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $pdf = $this->pdfParser->parseFile($file->path());
            $pdfDetails = $pdf->getDetails();

            $metadata['page_count'] = count($pdf->getPages());

            foreach ($pdfDetails as $property => $value) {
                if (!empty($value)) {
                    $metadata[strtolower($property)] = $value;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get PDF properties: ' . $e->getMessage());
        }
    }
}
