<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MetadataService
{
    public function __construct(
        private AssetStorageService $assetStorageService
    ) {}

    public function __invoke(
        UploadedFile $file,
        int $assetId,
        string $disk,
        string $hashName,
        ?string $thumbnailUrl
    ): void {
        $metadata = [
            'hash_name' => $hashName,
        ];

        if ($thumbnailUrl) {
            $metadata['thumbnail_url'] = $thumbnailUrl;
        }

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            $metadata['width'] = $image->width();
            $metadata['height'] = $image->height();
        }

        $this->assetStorageService->storeMetadata($assetId, $disk, $metadata);
    }
}
