<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ThumbnailService
{
    public function __invoke(UploadedFile $file, int $assetId, string $disk): ?string
    {
        if (!str_starts_with($file->getMimeType(), 'image/')) {
            return null;
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $image->cover(150, 150);

        $assetDirectory = 'uploads/' . $assetId;
        $thumbnailPath = $assetDirectory . '/thumbnail.' . $file->getClientOriginalExtension();

        Storage::disk($disk)->put($thumbnailPath, $image->encode());

        return Storage::disk($disk)->url($thumbnailPath);
    }
}
