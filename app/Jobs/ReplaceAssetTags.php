<?php

namespace App\Jobs;

use App\Services\TagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReplaceAssetTags implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $assetId,
        public array $tags
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TagService $tagService): void
    {
        $tagService->replaceAll($this->assetId, $this->tags);
    }
}
