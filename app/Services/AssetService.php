<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AssetService
{
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
        $path = Storage::disk($disk)->putFile('uploads', $file);
        $url = Storage::disk($disk)->url($path);

        $assetData = array_merge($data, [
            'file_name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => $url,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'metadata' => json_encode([]), // Initialize with empty JSON
        ]);

        return Asset::create($assetData);
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
