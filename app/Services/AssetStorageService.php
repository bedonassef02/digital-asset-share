<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetStorageService
{
    public function __invoke(UploadedFile $file, int $assetId, string $disk): array
    {
        $assetDirectory = 'uploads/' . $assetId;
        Storage::disk($disk)->makeDirectory($assetDirectory);

        $extension = $file->getClientOriginalExtension();
        $fileName = 'file';
        $path = Storage::disk($disk)->putFileAs($assetDirectory, $file, $fileName);
        $url = Storage::disk($disk)->url($path);
        $fileHash = hash_file('sha256', $file->getRealPath());

        return [
            'path' => $path,
            'url' => $url,
            'file_hash' => $fileHash,
            'extension' => $extension,
        ];
    }

    public function storeMetadata(int $assetId, string $disk, array $metadata): string
    {
        $assetDirectory = 'uploads/' . $assetId;
        $metadataFilePath = $assetDirectory . '/metadata.json';

        Storage::disk($disk)->put($metadataFilePath, json_encode($metadata, JSON_PRETTY_PRINT));

        return Storage::disk($disk)->url($metadataFilePath);
    }
}
