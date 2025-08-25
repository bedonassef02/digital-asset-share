<?php

declare(strict_types=1);

namespace App\Services\Serve;

use App\Models\Asset;
use App\Services\Asset\AssetService;
use App\Services\Asset\StorageService;
use App\Services\Download\DownloadService;

class ServeService
{
    public function __construct(
        private AssetService $assetService,
        private StorageService $storageService,
        private DownloadService $downloadService
    ) {}

    public function __invoke(int $assetId, int $userId, \Illuminate\Contracts\Auth\Authenticatable $authenticatedUser): array
    {
        $asset = $this->assetService->findOne($assetId, $userId);

        if ($authenticatedUser->getAuthIdentifier() !== $asset->user_id) {
            $this->downloadService->record($authenticatedUser, $asset);
        }

        $path = $this->storageService->getAssetVersionFilePath($asset->id, $asset->latestVersion->version);

        return ['asset' => $asset, 'path' => $path];
    }
}
