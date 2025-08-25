<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AssetVersion;
use App\Services\PathService;
use FFMpeg\FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TranscodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AssetVersion $assetVersion
    ) {}

    public function handle(PathService $pathService): void
    {
        $originalPath = $pathService->getAssetVersionFilePath($this->assetVersion->asset_id, $this->assetVersion->version);

        $transcodedPath = $pathService->getAssetVersionFilePath($this->assetVersion->asset_id, $this->assetVersion->version, PathService::DEFAULT_FILENAME.'.mp4');

        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open(Storage::disk()->path($originalPath));
        $video->save(new \FFMpeg\Format\Video\X264, Storage::disk()->path($transcodedPath));

        Storage::disk()->delete($originalPath);

        $this->assetVersion->update([
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'size' => Storage::disk()->size($transcodedPath),
        ]);
    }
}
