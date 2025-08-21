<?php

namespace App\Services;

use App\Jobs\GenerateThumbnail;
use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AssetService
{
    public function __construct(
        private AssetStorageService $assetStorageService,
        private MetadataService $metadataService
    ) { }

    public function findAll()
    {
        return Asset::all();
    }

    public function findOne(int $id)
    {
        return Asset::findOrFail($id);
    }

    public function create(UploadedFile $file, array $data = []): Asset
    {
        $assetData = $this->prepareData($file, $data);
        $asset = Asset::create($assetData);

        ($this->assetStorageService)($file, $asset->id);

        GenerateThumbnail::dispatch($asset);

        ($this->metadataService)($file, $asset);

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
        $asset = Asset::findOrFail($id);
        $asset->update($data);
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
}