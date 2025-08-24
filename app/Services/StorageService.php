<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    public function __construct(
        private PathService $pathService
    ) {}

    public function store(UploadedFile $file, int $assetId, int $version): string
    {
        $assetDirectory = $this->pathService->getAssetVersionPath($assetId, $version);
        Storage::disk()->makeDirectory($assetDirectory);

        return Storage::disk()->putFileAs($assetDirectory, $file, PathService::DEFAULT_FILENAME);
    }

    public function deleteDirectory(int $assetId): bool
    {
        $assetDirectory = $this->pathService->getAssetPath($assetId);
        return Storage::disk()->deleteDirectory($assetDirectory);
    }

    public function deleteVersion(int $assetId, int $version): bool
    {
        $assetDirectory = $this->pathService->getAssetVersionPath($assetId, $version);
        return Storage::disk()->deleteDirectory($assetDirectory);
    }

    public function getAssetVersionFilePath(int $assetId, int $version): string
    {
        $versionPath = $this->pathService->getAssetVersionPath($assetId, $version);
        $transcodedPath = $versionPath . '/' . PathService::DEFAULT_FILENAME . '.mp4';

        if (Storage::disk()->exists($transcodedPath)) {
            return $transcodedPath;
        }

        return $this->pathService->getAssetVersionFilePath($assetId, $version);
    }
}