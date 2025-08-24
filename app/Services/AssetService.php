<?php

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Jobs\ReplaceAssetTags;
use App\Jobs\TranscodeVideo;
use App\Models\Asset;
use App\Models\AssetVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(
        private StorageService $storageService,
        private MetadataService $metadataService
    ) { }

    public function findAll(int $userId, int $perPage = 15)
    {
        return Asset::with('latestVersion')->where('user_id', $userId)->paginate($perPage);
    }

    public function findOne(int $id, int $userId)
    {
        return Asset::with('latestVersion')->where('user_id', $userId)->findOrFail($id);
    }

    public function create(UploadedFile $file, array $data = []): Asset
    {
        return DB::transaction(function () use ($file, $data) {
            $asset = Asset::create(['user_id' => $data['user_id']]);

            $version = $this->createVersion($asset, $file, $data);

            $asset->update(['latest_version_id' => $version->id]);

            return $asset;
        });
    }

    public function createNewVersion(int $assetId, UploadedFile $file, array $data = []): AssetVersion
    {
        return DB::transaction(function () use ($assetId, $file, $data) {
            $asset = Asset::findOrFail($assetId);
            $version = $this->createVersion($asset, $file, $data);
            $asset->update(['latest_version_id' => $version->id]);
            return $version;
        });
    }

    private function createVersion(Asset $asset, UploadedFile $file, array $data): AssetVersion
    {
        $versionNumber = ($asset->versions()->max('version') ?? 0) + 1;

        $version = $asset->versions()->create(
            array_merge($this->prepareData($file, $data), ['version' => $versionNumber])
        );

        $this->storageService->store($file, $asset->id, $versionNumber);

        if (str_starts_with($version->mime_type, 'image/')) {
            GenerateThumbnail::dispatch($version);
        } elseif (str_starts_with($version->mime_type, 'video/')) {
            GenerateThumbnail::dispatch($version);
            TranscodeVideo::dispatch($version);
        }

        ($this->metadataService)($file, $version);

        return $version;
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

    public function update(int $id, array $data): Asset
    {
        $asset = Asset::findOrFail($id);

        $asset->latestVersion->update($data);

        if (isset($data['tags'])) {
            ReplaceAssetTags::dispatch($id, $data['tags']);
        }

        return $asset;
    }

    public function softDelete(int $id): bool
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();
        return true;
    }

    public function forceDelete(int $id): bool
    {
        $asset = Asset::withTrashed()->findOrFail($id);

        $this->storageService->deleteDirectory($id);

        $asset->forceDelete();
        return true;
    }
}
