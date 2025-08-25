<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;

class ServeService
{
    public function __construct(
        private AssetService $assetService,
        private StorageService $storageService
    ) {}

    public function __invoke(int $id, int $userId): ?Asset
    {
        return $this->assetService->findOne($id, $userId);
    }

    public function getAssetPath(Asset $asset): string
    {
        return $this->storageService->getAssetVersionFilePath($asset->id, $asset->latestVersion->version, PathService::DEFAULT_FILENAME);
    }
}
