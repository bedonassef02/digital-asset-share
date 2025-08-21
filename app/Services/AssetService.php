<?php

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Models\Asset;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;

class AssetService
{
    public function __construct(
        private AssetStorageService $assetStorageService,
        private MetadataService $metadataService
    ) { }

    public function findAll(int $userId, int $perPage = 15)
    {
        return Asset::where('user_id', $userId)->paginate($perPage);
    }

    public function findOne(int $id, int $userId)
    {
        return Asset::where('user_id', $userId)->findOrFail($id);
    }

    public function create(UploadedFile $file, array $data = []): Asset
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $assetData = $this->prepareData($file, $data);
        $asset = Asset::create($assetData);

        ($this->assetStorageService)($file, $asset->id);

        if (str_starts_with($asset->mime_type, 'image/') || str_starts_with($asset->mime_type, 'video/')) {
            GenerateThumbnail::dispatch($asset);
        }

        ($this->metadataService)($file, $asset);

        if (!empty($tags)) {
            $this->syncTags($asset->id, $tags);
        }

        return $asset;
    }

    private function prepareData(UploadedFile $file, array $data): array
    {
        return array_merge($data, [
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ]);
    }

    public function update(int $id, array $data)
    {
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $asset = Asset::findOrFail($id);
        $asset->update($data);

        if ($tags !== null) {
            $this->syncTags($asset->id, $tags);
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

        $this->assetStorageService->deleteDirectory($id);

        $asset->forceDelete();
        return true;
    }

    public function syncTags(int $assetId, array $tags): void
    {
        $asset = Asset::findOrFail($assetId);
        $tagIds = [];
        foreach ($tags as $tagName) {
            $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }
        $asset->tags()->sync($tagIds);
    }
}