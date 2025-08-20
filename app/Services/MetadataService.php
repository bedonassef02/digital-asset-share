<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MetadataService
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    public function __invoke(
        UploadedFile $file,
        Asset $asset,
    ): void {
        $metadata['hash'] = hash_file('sha256', $file->getRealPath());

        $this->mediaService->setProperties($file, $metadata);

        $this->store($asset->id, $metadata);
    }

    public function store(int $assetId, array $metadata): string
    {
        $assetDirectory = 'uploads/' . $assetId;
        $metadataFilePath = $assetDirectory . '/metadata.json';

        return Storage::disk()->put($metadataFilePath, json_encode($metadata, JSON_PRETTY_PRINT));
    }
}
