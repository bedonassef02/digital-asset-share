<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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

        $assetDirectory = 'uploads/' . $asset->id;
        Storage::disk($disk)->makeDirectory($assetDirectory);

        $originalFileName = $file->getClientOriginalName();
        $path = Storage::disk($disk)->putFileAs($assetDirectory, $file, $originalFileName);
        $url = Storage::disk($disk)->url($path);

        $asset->path = $path;
        $asset->url = $url;

        $metadata = json_decode($asset->metadata, true);
        $metadata['hash_name'] = basename($path);

        // Generate thumbnail if it's an image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            $image->cover(150, 150);

            $thumbnailPath = $assetDirectory . '/thumbnail.' . $file->getClientOriginalExtension();
            Storage::disk($disk)->put($thumbnailPath, $image->encode());
            $metadata['thumbnail_url'] = Storage::disk($disk)->url($thumbnailPath);
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
