<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetStorageService
{
    public function __invoke(UploadedFile $file, int $assetId, int $version): string
    {
        $assetDirectory = 'uploads/' . $assetId . '/' . $version;
        Storage::disk()->makeDirectory($assetDirectory);

        return Storage::disk()->putFileAs($assetDirectory, $file, 'file');
    }

    public function deleteDirectory(int $assetId): bool
    {
        $assetDirectory = 'uploads/' . $assetId;
        return Storage::disk()->deleteDirectory($assetDirectory);
    }

    public function deleteVersion(int $assetId, int $version): bool
    {
        $assetDirectory = 'uploads/' . $assetId . '/' . $version;
        return Storage::disk()->deleteDirectory($assetDirectory);
    }
}