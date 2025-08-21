<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Services\MetadataService;
use App\Services\ImageThumbnailService;
use App\Services\VideoThumbnailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $asset;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Asset  $asset
     * @return void
     */
    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\ThumbnailService  $thumbnailService
     * @param  \App\Services\VideoThumbnailService  $videoThumbnailService
     * @param  \App\Services\MetadataService  $metadataService
     * @return void
     */
    public function handle(
        ImageThumbnailService $thumbnailService,
        VideoThumbnailService $videoThumbnailService,
        MetadataService $metadataService
    ): void {
        Log::info('GenerateThumbnail job started for Asset ID: ' . $this->asset->id);

        Log::info($this->asset);
        try {
            if (str_starts_with($this->asset->mime_type, 'image/')) {
                $thumbnailService->generate($this->asset);
            } elseif (str_starts_with($this->asset->mime_type, 'video/')) {
                Log::info("Generating video thumbnail for Asset ID: " . $this->asset->id);
                $videoThumbnailService->generate($this->asset);
            }

            $metadataService->update($this->asset->id, ['has_thumbnail' => true]);
            Log::info('Successfully generated thumbnail for Asset ID: ' . $this->asset->id);
        } catch (\Exception $e) {
            Log::error('Error generating thumbnail for Asset ID: ' . $this->asset->id . ': ' . $e->getMessage());
        }

        Log::info('GenerateThumbnail job finished for Asset ID: ' . $this->asset->id);
    }
}
