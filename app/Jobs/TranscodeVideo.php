<?php

namespace App\Jobs;

use App\Models\AssetVersion;
use App\Services\AssetStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;

class TranscodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AssetVersion $assetVersion
    ) {}

    public function handle(AssetStorageService $assetStorageService): void
    {
        $originalPath = $assetStorageService->getAssetVersionPath($this->assetVersion->asset_id, $this->assetVersion->version) . '/file';

        $transcodedPath = $assetStorageService->getAssetVersionPath($this->assetVersion->asset_id, $this->assetVersion->version) . '/file.mp4';

        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open(Storage::disk()->path($originalPath));
        $video->save(new \FFMpeg\Format\Video\X264(), Storage::disk()->path($transcodedPath));

        Storage::disk()->delete($originalPath);

        $this->assetVersion->update([
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'size' => Storage::disk()->size($transcodedPath),
        ]);
    }
}
