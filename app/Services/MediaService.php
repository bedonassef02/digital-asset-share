<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    public function __construct(
        private ?ImageManager $imageManager = null
    ) {
        $this->imageManager ??= new ImageManager(new Driver());
    }

    public function setProperties(UploadedFile $file, array &$metadata): void
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $image = $this->imageManager->read($file->getRealPath());

            $metadata['width'] = $image->width();
            $metadata['height'] = $image->height();
            $metadata['has_thumbnail'] = true;
        }
    }
}
