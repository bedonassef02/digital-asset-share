<?php

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Jobs\ReplaceAssetTags;
use App\Jobs\TranscodeVideo;
use App\Models\Asset;
use App\Models\AssetVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            $fileHash = $this->calculateFileHash($file);

            $existingVersion = AssetVersion::where('file_hash', $fileHash)
                ->whereHas('asset', function ($query) use ($data) {
                    $query->where('user_id', $data['user_id']);
                })
                ->first();

            if ($existingVersion) {
                $asset = Asset::create(['user_id' => $data['user_id']]);
                $asset->update(['latest_version_id' => $existingVersion->id]);
                $asset->load('latestVersion');
                return $asset;
            }

            $asset = Asset::create(['user_id' => $data['user_id']]);

            $version = $this->createVersion($asset, $file, $data, $fileHash);

            $asset->update(['latest_version_id' => $version->id]);

            $asset->load('latestVersion'); // Eager load latestVersion

            return $asset;
        });
    }

    public function createNewVersion(int $assetId, UploadedFile $file, array $data = []): AssetVersion
    {
        return DB::transaction(function () use ($assetId, $file, $data) {
            $asset = Asset::findOrFail($assetId);
            $fileHash = $this->calculateFileHash($file);

            $existingVersion = AssetVersion::where('file_hash', $fileHash)
                ->whereHas('asset', function ($query) use ($asset) {
                    $query->where('user_id', $asset->user_id);
                })
                ->first();

            if ($existingVersion) {
                $asset->update(['latest_version_id' => $existingVersion->id]);
                return $existingVersion;
            }

            $version = $this->createVersion($asset, $file, $data, $fileHash);
            $asset->update(['latest_version_id' => $version->id]);
            return $version;
        });
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

    private function calculateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
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
