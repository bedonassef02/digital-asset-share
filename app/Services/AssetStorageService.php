<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetStorageService
{
    private const SHARD_SIZE = 1000;

    public function __invoke(UploadedFile $file, int $assetId, int $version): string
    {
        $assetDirectory = $this->getAssetVersionPath($assetId, $version);
        Storage::disk()->makeDirectory($assetDirectory);

        return Storage::disk()->putFileAs($assetDirectory, $file, 'file');
    }

    public function deleteDirectory(int $assetId): bool
    {
        $assetDirectory = $this->getAssetPath($assetId);
        return Storage::disk()->deleteDirectory($assetDirectory);
    }

    public function deleteVersion(int $assetId, int $version): bool
    {
        $assetDirectory = $this->getAssetVersionPath($assetId, $version);
        return Storage::disk()->deleteDirectory($assetDirectory);
    }

    private function getShardDirectory(int $assetId): string
    {
        $shardId = floor(($assetId - 1) / self::SHARD_SIZE);
        return 'uploads/' . $shardId;
    }

    private function getAssetPath(int $assetId): string
    {
        return $this->getShardDirectory($assetId) . '/' . $assetId;
    }

    public function getAssetVersionPath(int $assetId, int $version): string
    {
        return $this->getAssetPath($assetId) . '/' . $version;
    }

    public function getAssetVersionFilePath(int $assetId, int $version, string $fileName = 'file'): string
    {
        $versionPath = $this->getAssetVersionPath($assetId, $version);
        $transcodedPath = $versionPath . '/file.mp4';

        if (Storage::disk()->exists($transcodedPath)) {
            return $transcodedPath;
        }

        return $versionPath . '/' . $fileName;
    }
}