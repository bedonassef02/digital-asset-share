<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Jobs\TranscodeVideo;
use App\Models\Asset;
use App\Models\AssetVersion;
use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;

class AssetVersionService
{
    public function __construct(
        private StorageService $storageService,
        private MetadataService $metadataService,
        private FileHashService $fileHashService
    ) {}

    public function findAssetVersionByHash(string $fileHash, int $userId): ?AssetVersion
    {
        return AssetVersion::where('file_hash', $fileHash)
            ->whereHas('asset', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->first();
    }

    public function findOrCreateVersion(Asset $asset, UploadedFile $file, array $data, string $fileHash): AssetVersion
    {
        $existingVersion = $this->findAssetVersionByHash($fileHash, $asset->user_id);
        if ($existingVersion) {
            return $existingVersion;
        }

        return $this->createVersion($asset, $file, $data, $fileHash);
    }

    public function createVersion(Asset $asset, UploadedFile $file, array $data, string $fileHash): AssetVersion
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

    public function createNewVersion(int $assetId, UploadedFile $file, int $userId, array $data = []): AssetVersion
    {
        return DB::transaction(function () use ($assetId, $file, $data, $userId) {
            $asset = Asset::where('user_id', $userId)->findOrFail($assetId);
            $fileHash = $this->fileHashService->calculateFileHash($file);
            $version = $this->findOrCreateVersion($asset, $file, $data, $fileHash);
            $asset->update(['latest_version_id' => $version->id]);

            return $version;
        });
    }

    public function deleteVersion(int $assetId, int $versionNumber): bool
    {
        $asset = Asset::findOrFail($assetId);
        $assetVersion = $asset->versions()->where('version', $versionNumber)->firstOrFail();

        // Prevent deleting the latest version directly
        if ($asset->latest_version_id === $assetVersion->id) {
            throw new \Exception('Cannot delete the latest version directly. Update the asset to a different version first.');
        }

        $this->storageService->deleteVersion($asset->id, $versionNumber);

        $assetVersion->delete();

        return true;
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

    public function getVersionsForAsset(int $assetId): \Illuminate\Database\Eloquent\Collection
    {
        $asset = Asset::with('versions')->findOrFail($assetId);

        return $asset->versions;
    }

    public function getSpecificVersion(int $assetId, string $version): AssetVersion
    {
        $asset = Asset::findOrFail($assetId);
        return $asset->versions()->where('version', $version)->firstOrFail();
    }
}
