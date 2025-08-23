<?php

namespace App\Services\Thumbnail;

use App\Models\AssetVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;

class VideoThumbnail
{
    public function __construct(
        private FFMpeg $ffmpeg
    ) { }

    public function generate(AssetVersion $assetVersion): ?string
    {
        $filePath = 'uploads/' . $assetVersion->asset_id . '/' . $assetVersion->version . '/file';
        $fullPath = Storage::disk()->path($filePath);

        $thumbnailPath = 'uploads/' . $assetVersion->asset_id . '/' . $assetVersion->version . '/thumbnail';
        $fullThumbnailPath = Storage::disk()->path($thumbnailPath);

        try {
            $video = $this->ffmpeg->open($fullPath);
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1)); // Get frame at 1 second mark
            $frame->save($fullThumbnailPath);

            return Storage::disk()->url($thumbnailPath);
        } catch (\Exception $e) {
            // Log the error, or handle it as appropriate
            // For now, just return null on failure
            Log::error($e->getMessage());
            return null;
        }
    }
}