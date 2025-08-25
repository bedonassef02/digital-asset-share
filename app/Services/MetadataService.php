<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetVersion;
use Illuminate\Http\UploadedFile;

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
