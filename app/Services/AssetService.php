<?php

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Jobs\ReplaceAssetTags;
use App\Jobs\TranscodeVideo;
use App\Models\Asset;
use App\Models\AssetVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AssetService
{
    public function __construct(
        private StorageService $storageService,
        private MetadataService $metadataService,
        private FileHashService $fileHashService,
        private ViewService $viewService
    ) { }

    public function findAll(int $userId, int $perPage = 15, bool $includeTrashed = false, bool $onlyTrashed = false)
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

    public function findOne(int $id, int $userId)
    {
        $asset = Asset::withTrashed()->with('latestVersion')->where('user_id', $userId)->findOrFail($id);

        $this->viewService->record(auth()->user(), $asset);

        return $asset;
    }

    public function create(UploadedFile $file, array $data = []): Asset
    {
        return DB::transaction(function () use ($file, $data) {
            $asset = Asset::create(['user_id' => $data['user_id']]);
            $version = $this->findOrCreateVersion($asset, $file, $data);
            $asset->update(['latest_version_id' => $version->id]);
            $asset->load('latestVersion');
            return $asset;
        });
    }

    public function createNewVersion(int $assetId, UploadedFile $file, array $data = [], int $userId): AssetVersion
    {
        return DB::transaction(function () use ($assetId, $file, $data, $userId) {
            $asset = Asset::where('user_id', $userId)->findOrFail($assetId);
            $version = $this->findOrCreateVersion($asset, $file, $data);
            $asset->update(['latest_version_id' => $version->id]);
            return $version;
        });
    }

    private function findOrCreateVersion(Asset $asset, UploadedFile $file, array $data): AssetVersion
    {
        $fileHash = $this->fileHashService->calculateFileHash($file);

        $existingVersion = AssetVersion::where('file_hash', $fileHash)
            ->whereHas('asset', function ($query) use ($asset) {
                $query->where('user_id', $asset->user_id);
            })
            ->first();

        if ($existingVersion) {
            return $existingVersion;
        }

        return $this->createVersion($asset, $file, $data, $fileHash);
    }

    private function createVersion(Asset $asset, UploadedFile $file, array $data, string $fileHash): AssetVersion
    {
        $versionNumber = ($asset->versions()->max('version') ?? 0) + 1;

        $version = $asset->versions()->create(
            array_merge($this->prepareData($file, $data), [
                'version' => $versionNumber,
                'file_hash' => $fileHash,
            ])
        );

        $this->storageService->store($file, $asset->id, $versionNumber);

        $this->dispatchMediaJobs($version);

        ($this->metadataService)($file, $version);

        return $version;
    }

    private function dispatchMediaJobs(AssetVersion $version): void
    {
        if (str_starts_with($version->mime_type, 'image/')) {
            GenerateThumbnail::dispatch($version);
        } elseif (str_starts_with($version->mime_type, 'video/')) {
            GenerateThumbnail::dispatch($version);
            TranscodeVideo::dispatch($version);
        }
    }

    private function prepareData(UploadedFile $file, array $data): array
    {
        return [
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'description' => $data['description'] ?? null,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];
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

    public function delete(int $id, bool $force = false, int $userId): bool
    {
        if ($force) {
            return $this->forceDelete($id, $userId);
        }

        return $this->softDelete($id, $userId);
    }
}
