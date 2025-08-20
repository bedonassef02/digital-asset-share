<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    public function setProperties(UploadedFile $file, array &$metadata): void
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            $metadata['width'] = $image->width();
            $metadata['height'] = $image->height();
        }
    }
}
