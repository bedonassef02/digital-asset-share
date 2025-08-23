<?php

namespace App\Services;

use App\Models\AssetVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MetadataService
{
    public function __construct(
        private MediaService $mediaService,
        private AssetStorageService $storageService
    ) {}

    public function __invoke(
        UploadedFile $file,
        AssetVersion $assetVersion,
    ): void {
        $metadata['hash'] = hash_file('sha256', $file->getRealPath());

        $this->mediaService->setProperties($file, $metadata);

        $this->store($assetVersion, $metadata);
    }

    public function store(AssetVersion $assetVersion, array $metadata): string
    {
        $metadataFilePath = $this->getMetadataFilePath($assetVersion);

        return Storage::disk()->put($metadataFilePath, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    public function update(AssetVersion $assetVersion, array $data): void
    {
        $metadataFilePath = $this->getMetadataFilePath($assetVersion);

        if (Storage::disk()->exists($metadataFilePath)) {
            $metadata = json_decode(Storage::disk()->get($metadataFilePath), true);
            $updatedMetadata = array_merge($metadata, $data);
            $this->store($assetVersion, $updatedMetadata);
        }
    }

    private function getMetadataFilePath(AssetVersion $assetVersion): string
    {
        return $this->storageService->getAssetVersionPath($assetVersion->asset_id, $assetVersion->version) . '/metadata.json';
    }
}