<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetStorageService
{
    public function __invoke(UploadedFile $file, int $assetId): array
    {
        $assetDirectory = 'uploads/' . $assetId;
        Storage::disk()->makeDirectory($assetDirectory);

        $extension = $file->getClientOriginalExtension();
        $fileName = 'file';
        $path = Storage::disk()->putFileAs($assetDirectory, $file, $fileName);
        $url = Storage::disk()->url($path);
        $fileHash = hash_file('sha256', $file->getRealPath());

        return [
            'path' => $path,
            'url' => $url,
            'file_hash' => $fileHash,
            'extension' => $extension,
        ];
    }

    public function storeMetadata(int $assetId, array $metadata): string
    {
        $assetDirectory = 'uploads/' . $assetId;
        $metadataFilePath = $assetDirectory . '/metadata.json';

        Storage::disk()->put($metadataFilePath, json_encode($metadata, JSON_PRETTY_PRINT));

        return Storage::disk()->url($metadataFilePath);
    }
}
