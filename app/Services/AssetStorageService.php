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

        $originalFileName = $file->getClientOriginalName();
        $path = Storage::disk($disk)->putFileAs($assetDirectory, $file, $originalFileName);
        $url = Storage::disk($disk)->url($path);

        return [
            'path' => $path,
            'url' => $url,
            'hash_name' => basename($path),
        ];
    }
}
