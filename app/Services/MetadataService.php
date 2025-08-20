<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MetadataService
{
    public function __construct(
        private AssetStorageService $assetStorageService,
        private MediaService $mediaService
    ) {}

    public function __invoke(
        UploadedFile $file,
        int $assetId,
        string $disk,
        string $fileHash,
        ?string $thumbnailUrl
    ): void
    {
        $metadata = [
            'file_hash' => $fileHash,
        ];

        if ($thumbnailUrl) {
            $metadata['thumbnail_url'] = $thumbnailUrl;
        }

        $this->mediaService->setProperties($file, $metadata);

        $this->assetStorageService->storeMetadata($assetId, $disk, $metadata);
    }

    public function findOne(Asset $asset)
    {
        $metadataFilePath = 'uploads/' . $asset->id . '/metadata.json';

        if (Storage::disk($asset->disk)->exists($metadataFilePath)) {
            $metadataContent = Storage::disk($asset->disk)->get($metadataFilePath);
            $asset->metadata = json_decode($metadataContent, true);
        } else {
            $asset->metadata = [];
        }

        return $asset;
    }
}
