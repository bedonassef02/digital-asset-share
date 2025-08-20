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

        return [
            'path' => $path,
            'url' => $url,
            'hash_name' => pathinfo($path, PATHINFO_FILENAME),
            'extension' => $extension,
        ];
    }
}
