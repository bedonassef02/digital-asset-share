<?php

declare(strict_types=1);

namespace App\Services\Asset\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageProcessor implements \App\Services\Asset\MediaProcessors\MediaProcessorInterface
{
    public function __construct(
        private ?ImageManager $imageManager = null
    ) {
        $this->imageManager ??= new ImageManager(new Driver);
    }

    public function canProcess(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        $image = $this->imageManager->read($file->getRealPath());

        $metadata['width'] = $image->width();
        $metadata['height'] = $image->height();

        try {
            $exifData = $image->exif();
            if ($exifData) {
                foreach ($exifData as $key => $value) {
                    $metadata['exif_'.strtolower($key)] = $value;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not read EXIF data for image: '.$e->getMessage());
        }
    }
}
