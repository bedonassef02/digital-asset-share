<?php

namespace App\Services;

use App\Models\AssetVersion;
use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MetadataService
{
    public function __construct(
        private MediaService $mediaService,
    ) {}

    public function __invoke(
        UploadedFile $file,
        AssetVersion $assetVersion,
    ): void {
        $metadata = [];
        $metadata['hash'] = hash_file('sha256', $file->getRealPath());

        $this->mediaService->setProperties($file, $metadata);

        $assetVersion->update(['metadata' => $metadata]);
    }

    public function update(AssetVersion $assetVersion, array $data): void
    {
        $currentMetadata = $assetVersion->metadata ?? [];
        $updatedMetadata = array_merge($currentMetadata, $data);
        $assetVersion->update(['metadata' => $updatedMetadata]);
    }
}