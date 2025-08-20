<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AssetService
{
    public function __construct(
        private ThumbnailService $thumbnailService,
        private AssetStorageService $assetStorageService)
    { }

    public function findAll()
    {
        return Asset::all();
    }

    public function findOne(int $id)
    {
        return Asset::findOrFail($id);
    }

    public function create(UploadedFile $file, string $disk, array $data = []): Asset
    {
        $assetData = $this->prepareAssetData($file, $disk, $data);
        $asset = Asset::create($assetData);

        $this->storeFile($file, $asset, $disk);
        $this->generateThumbnail($file, $asset, $disk);

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
            'metadata' => json_encode([]),
        ]);
    }

    private function storeFile(UploadedFile $file, Asset $asset, string $disk): void
    {
        $storedAsset = ($this->assetStorageService)($file, $asset->id, $disk);

        $asset->path = $storedAsset['path'];
        $asset->url = $storedAsset['url'];
        $asset->extension = $storedAsset['extension'];

        $metadata = json_decode($asset->metadata, true);
        $metadata['hash_name'] = $storedAsset['hash_name'];
        $asset->metadata = json_encode($metadata);
    }

    private function generateThumbnail(UploadedFile $file, Asset $asset, string $disk): void
    {
        $thumbnailUrl = ($this->thumbnailService)($file, $asset->id, $disk);

        if ($thumbnailUrl) {
            $metadata = json_decode($asset->metadata, true);
            $metadata['thumbnail_url'] = $thumbnailUrl;
            $asset->metadata = json_encode($metadata);
        }
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