<?php

namespace App\Services;

use App\Models\Asset;

class ServeService
{
    public function __construct(
        private AssetService $assetService,
        private AssetStorageService $storageService
    ) {}

    public function __invoke(string $id): ?Asset
    {
        return $this->assetService->findOne($id, auth()->id());
    }

    public function getAssetPath(Asset $asset): string
    {
        return $this->storageService->getAssetVersionFilePath($asset->id, $asset->latestVersion->version);
    }
}
