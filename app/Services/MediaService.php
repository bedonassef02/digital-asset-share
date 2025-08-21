<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Smalot\PdfParser\Parser;

class MediaService
{
    public function __construct(
        private ?ImageManager $imageManager = null,
        private ?Parser $pdfParser = null
    ) {
        $this->imageManager ??= new ImageManager(new Driver());
        $this->pdfParser ??= new Parser();
    }

    public function setProperties(UploadedFile $file, array &$metadata): void
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $image = $this->imageManager->read($file->getRealPath());

            $metadata['width'] = $image->width();
            $metadata['height'] = $image->height();
        } elseif ($mimeType === 'application/pdf') {
            $pdf = $this->pdfParser->parseFile($file->path());
            $pdfDetails = $pdf->getDetails();

            foreach ($pdfDetails as $property => $value) {
                if (!empty($value)) {
                    $metadata[strtolower($property)] = $value;
                }
            }
        }
    }
}
