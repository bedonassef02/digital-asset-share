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
        return Asset::all()->map(fn ($asset) => $this->attachMetadata($asset));
    }

    public function findOne(int $id)
    {
        $asset = Asset::findOrFail($id);
        return $this->attachMetadata($asset);
    }

    private function attachMetadata(Asset $asset): Asset
    {
        $metadataFilePath = 'uploads/' . $asset->id . '/metadata.json';

        if (Storage::disk($asset->disk)->exists($metadataFilePath)) {
            $metadataContent = Storage::disk($asset->disk)->get($metadataFilePath);
            $asset->metadata = json_decode($metadataContent, true);
        } else {
            $asset->metadata = [];
        }

        return $asset;
    }

    public function create(UploadedFile $file, string $disk, array $data = []): Asset
    {
        $assetData = $this->prepareAssetData($file, $disk, $data);
        $asset = Asset::create($assetData);

        $hashName = $this->storeFile($file, $asset, $disk);
        $thumbnailUrl = ($this->thumbnailService)($file, $asset->id, $disk);

        ($this->metadataService)($file, $asset->id, $disk, $hashName, $thumbnailUrl);

        $asset->save();

        return $asset;
    }

    private function prepareAssetData(UploadedFile $file, string $disk, array $data): array
    {
        return array_merge($data, [
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'extension' => $file->getClientOriginalExtension(),
        ]);
    }

    private function storeFile(UploadedFile $file, Asset $asset, string $disk): string
    {
        $storedAsset = ($this->assetStorageService)($file, $asset->id, $disk);

        $asset->path = $storedAsset['path'];
        $asset->url = $storedAsset['url'];
        $asset->extension = $storedAsset['extension'];

        return $storedAsset['hash_name'];
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

        Storage::disk($asset->disk)->delete($asset->path);

        $asset->delete();
        return true;
    }
}