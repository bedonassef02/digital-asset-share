<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Services\ThumbnailService;
use App\Services\AssetStorageService;

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

    public function create(UploadedFile $file, string $disk, array $data = [])
    {
        $assetData = array_merge($data, [
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'metadata' => json_encode([
                'extension' => $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
            ]),
        ]);

        $asset = Asset::create($assetData);

        $storedAsset = ($this->assetStorageService)($file, $asset->id, $disk);

        $asset->path = $storedAsset['path'];
        $asset->url = $storedAsset['url'];

        $metadata = json_decode($asset->metadata, true);
        $metadata['hash_name'] = $storedAsset['hash_name'];

        $thumbnailUrl = ($this->thumbnailService)($file, $asset->id, $disk);

        if ($thumbnailUrl) {
            $metadata['thumbnail_url'] = $thumbnailUrl;
        }

        $asset->metadata = json_encode($metadata);
        $asset->save();

        return $asset;
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
