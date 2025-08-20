<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AssetService
{
    public function __construct(
        private ThumbnailService $thumbnailService,
        private AssetStorageService $assetStorageService,
        private MetadataService $metadataService
    ) { }

    public function findAll()
    {
        return Asset::all()->map(fn ($asset) => $this->metadataService->findOne($asset));
    }

    public function findOne(int $id)
    {
        $asset = Asset::findOrFail($id);
        return $this->metadataService->findOne($asset);
    }

    public function create(UploadedFile $file, array $data = []): Asset
    {
        $assetData = $this->prepareAssetData($file, $data);
        $asset = Asset::create($assetData);

        $fileHash = $this->storeFile($file, $asset);
        $thumbnailUrl = ($this->thumbnailService)($file, $asset->id);

        ($this->metadataService)($file, $asset->id, $fileHash, $thumbnailUrl);

        $asset->save();

        return $asset;
    }

    private function prepareAssetData(UploadedFile $file, array $data): array
    {
        return array_merge($data, [
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ]);
    }

    private function storeFile(UploadedFile $file, Asset $asset): string
    {
        $storedAsset = ($this->assetStorageService)($file, $asset->id);

        $asset->path = $storedAsset['path'];
        $asset->url = $storedAsset['url'];
        $asset->extension = $storedAsset['extension'];

        return $storedAsset['file_hash'];
    }

    public function update(int $id, array $data)
    {
        $asset = Asset::findOrFail($id);
        $asset->update($data);
        return $asset;
    }

    public function delete(int $id)
    {
        $asset = Asset::findOrFail($id);

        Storage::disk()->delete($asset->path);

        $asset->delete();
        return true;
    }
}