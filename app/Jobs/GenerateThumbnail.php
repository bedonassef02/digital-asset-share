<?php

namespace App\Jobs;

use App\Models\AssetVersion;
use App\Services\MetadataService;
use App\Services\Thumbnail\ImageThumbnail;
use App\Services\Thumbnail\VideoThumbnail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\AssetVersion  $assetVersion
     * @return void
     */
    public function __construct(
        private AssetVersion $assetVersion
    ) {
        $this->assetVersion->load('asset');
    }

    /**
     * Execute the job.
     *
     * @param  \App\Services\Thumbnail\ImageThumbnail  $thumbnailService
     * @param  \App\Services\Thumbnail\VideoThumbnail  $videoThumbnailService
     * @param  \App\Services\MetadataService  $metadataService
     * @return void
     */
    public function handle(
        ImageThumbnail $imageThumbnail,
        VideoThumbnail $videoThumbnail,
        MetadataService $metadataService,
        \App\Services\NotificationService $notificationService
    ): void {
        Log::info('GenerateThumbnail job started for Asset Version ID: ' . $this->assetVersion->id);

        Log::info($this->assetVersion);
        try {
            if (str_starts_with($this->assetVersion->mime_type, 'image/')) {
                $imageThumbnail->generate($this->assetVersion);
            } elseif (str_starts_with($this->assetVersion->mime_type, 'video/')) {
                Log::info("Generating video thumbnail for Asset Version ID: " . $this->assetVersion->id);
                $videoThumbnail->generate($this->assetVersion);
            }

            $metadataService->update($this->assetVersion, ['has_thumbnail' => true]);
            Log::info('Successfully generated thumbnail for Asset Version ID: ' . $this->assetVersion->id);

            $notificationService->create(
                $this->assetVersion->asset->user_id,
                'success',
                'Thumbnail generated successfully for ' . $this->assetVersion->name,
                $this->assetVersion
            );
        } catch (\Exception $e) {
            Log::error('Error generating thumbnail for Asset Version ID: ' . $this->assetVersion->id . ': ' . $e->getMessage());

            $notificationService->create(
                $this->assetVersion->asset->user_id,
                'error',
                'Failed to generate thumbnail for ' . $this->assetVersion->name . ': ' . $e->getMessage(),
                $this->assetVersion
            );
        }

        Log::info('GenerateThumbnail job finished for Asset Version ID: ' . $this->assetVersion->id);
    }
}