<?php

declare(strict_types=1);

namespace App\Services\Asset;

use App\Jobs\ReplaceAssetTags;
use App\Models\Asset;
use App\Services\View\ViewService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(
        private FileHashService $fileHashService,
        private ViewService $viewService,
        private StorageService $storageService,
        private AssetVersionService $assetVersionService
    ) {}

    public function findAll(int $userId, int $perPage = 15, bool $includeTrashed = false, bool $onlyTrashed = false): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Asset::with('latestVersion')
            ->where('user_id', $userId);

        if ($includeTrashed) {
            $query->withTrashed();
        }

        if ($onlyTrashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($perPage);
    }

    public function findOne(int $id, int $userId): Asset
    {
        $asset = Asset::withTrashed()->with('latestVersion')->where('user_id', $userId)->findOrFail($id);

        if ($userId !== $asset->user_id) {
            $this->viewService->record($userId, $asset);
        }

        return $asset;
    }

    public function create(UploadedFile $file, array $data = []): Asset
    {
        $fileHash = $this->fileHashService->calculateFileHash($file);
        $existingAsset = $this->findExistingAssetByHash($fileHash, $data['user_id']);

        if ($existingAsset) {
            return $existingAsset;
        }

        return DB::transaction(function () use ($file, $data, $fileHash) {
            $asset = Asset::create(['user_id' => $data['user_id']]);
            $version = $this->assetVersionService->findOrCreateVersion($asset, $file, $data, $fileHash);
            $asset->update(['latest_version_id' => $version->id]);
            $asset->load('latestVersion');

            return $asset;
        });
    }

    private function findExistingAssetByHash(string $fileHash, int $userId): ?Asset
    {
        $existingVersion = $this->assetVersionService->findAssetVersionByHash($fileHash, $userId);

        return $existingVersion?->asset;
    }

    public function update(int $id, array $data, int $userId): Asset
    {
        $asset = Asset::where('user_id', $userId)->findOrFail($id);

        $asset->latestVersion->update($data);

        if (isset($data['tags'])) {
            ReplaceAssetTags::dispatch($id, $data['tags']);
        }

        return $asset;
    }

    public function changeStatus(int $id, string $status, int $userId): bool
    {
        $asset = Asset::withTrashed()->where('user_id', $userId)->findOrFail($id);
        $asset->update(['status' => $status]);

        return true;
    }

    public function softDelete(int $id, int $userId): bool
    {
        $asset = Asset::where('user_id', $userId)->findOrFail($id);
        $asset->delete(); // Uses SoftDeletes trait

        return true;
    }

    public function restore(int $id, int $userId): bool
    {
        $asset = Asset::onlyTrashed()->where('user_id', $userId)->findOrFail($id);
        $asset->restore(); // Uses SoftDeletes trait
        $asset->update(['status' => Asset::STATUS_ACTIVE]);

        return true;
    }

    public function forceDelete(int $id, int $userId): bool
    {
        $asset = Asset::withTrashed()->where('user_id', $userId)->findOrFail($id);

        $this->storageService->deleteDirectory($id);

        $asset->forceDelete();

        return true;
    }

    public function bulkSoftDelete(array $assetIds, int $userId): void
    {
        Asset::whereIn('id', $assetIds)->where('user_id', $userId)->delete(); // Uses SoftDeletes trait
    }

    public function delete(int $id, int $userId, bool $force = false): bool
    {
        if ($force) {
            return $this->forceDelete($id, $userId);
        }

        return $this->softDelete($id, $userId);
    }
}
