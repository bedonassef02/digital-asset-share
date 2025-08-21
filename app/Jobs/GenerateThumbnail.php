<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Services\ThumbnailService;
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
     * @return void
     */
    public function handle(ThumbnailService $thumbnailService): void
    {
        Log::info('GenerateThumbnail job started for Asset ID: ' . $this->asset->id);

        try {
            $thumbnailService->generate($this->asset);
            Log::info('Successfully generated thumbnail for Asset ID: ' . $this->asset->id);
        } catch (\Exception $e) {
            Log::error('Error generating thumbnail for Asset ID: ' . $this->asset->id . ': ' . $e->getMessage());
        }

        Log::info('GenerateThumbnail job finished for Asset ID: ' . $this->asset->id);
    }
}
