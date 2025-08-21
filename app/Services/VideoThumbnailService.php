<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoThumbnailService
{
    public function __construct(
        private FFMpeg $ffmpeg
    ) { }

    public function generate(Asset $asset): ?string
    {
        $filePath = 'uploads/' . $asset->id . '/file';
        $fullPath = Storage::disk()->path($filePath);

        $thumbnailPath = 'uploads/' . $asset->id . '/thumbnail';
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
