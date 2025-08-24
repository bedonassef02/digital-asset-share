<?php

namespace App\Services\Thumbnail;

use App\Models\AssetVersion;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Services\PathService;

class ImageThumbnail
{
    public function __construct(
        private PathService $pathService,
        private ?ImageManager $imageManager = null
    ) {
        $this->imageManager ??= new ImageManager(new Driver());
    }

    public function generate(AssetVersion $assetVersion): ?string
    {
        $filePath = $this->pathService->getAssetVersionFilePath($assetVersion->asset_id, $assetVersion->version);

        return $this->generateThumbnail(Storage::disk()->get($filePath), $assetVersion->asset_id, $assetVersion->version);
    }

    private function generateThumbnail(string $imageSource, int $assetId, int $version): string
    {
        $image = $this->imageManager->read($imageSource);
        $image->cover(150, 150);

        $assetDirectory = $this->pathService->getAssetVersionPath($assetId, $version);
        $thumbnailPath = $assetDirectory . '/thumbnail';

        Storage::disk()->put($thumbnailPath, $image->encode());

        return Storage::disk()->url($thumbnailPath);
    }
}