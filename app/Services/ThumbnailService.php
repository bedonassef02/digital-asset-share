<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ThumbnailService
{
    public function __construct(
        private ?ImageManager $imageManager = null
    ) {
        $this->imageManager ??= new ImageManager(new Driver());
    }

    public function generate(Asset $asset): ?string
    {
        $filePath = 'uploads/' . $asset->id . '/file';

        if (!str_starts_with($asset->mime_type, 'image/') || !Storage::disk()->exists($filePath)) {
            return null;
        }

        return $this->generateThumbnail(Storage::disk()->get($filePath), $asset->id);
    }

    private function generateThumbnail(string $imageSource, int $assetId): string
    {
        $image = $this->imageManager->read($imageSource);
        $image->cover(150, 150);

        $assetDirectory = 'uploads/' . $assetId;
        $thumbnailPath = $assetDirectory . '/thumbnail';

        Storage::disk()->put($thumbnailPath, $image->encode());

        return Storage::disk()->url($thumbnailPath);
    }
}
