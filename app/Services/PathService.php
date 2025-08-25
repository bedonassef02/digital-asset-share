<?php

declare(strict_types=1);

namespace App\Services;

class PathService
{
    private const SHARD_SIZE = 1000;

    public const UPLOADS_DIR = 'uploads';

    public const DEFAULT_FILENAME = 'file';

    public function getShardDirectory(int $assetId): string
    {
        $shardId = floor(($assetId - 1) / self::SHARD_SIZE);

        return self::UPLOADS_DIR.'/'.$shardId;
    }

    public function getAssetPath(int $assetId): string
    {
        return $this->getShardDirectory($assetId).'/'.$assetId;
    }

    public function getAssetVersionPath(int $assetId, int $version): string
    {
        return $this->getAssetPath($assetId).'/'.$version;
    }

    public function getAssetVersionFilePath(int $assetId, int $version, string $fileName = self::DEFAULT_FILENAME): string
    {
        return $this->getAssetVersionPath($assetId, $version).'/'.$fileName;
    }
}
