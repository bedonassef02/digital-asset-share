<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAssetTags implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assetId;
    protected $tags;

    /**
     * Create a new job instance.
     *
     * @param  int  $assetId
     * @param  array  $tags
     * @return void
     */
    public function __construct(int $assetId, array $tags)
    {
        $this->assetId = $assetId;
        $this->tags = $tags;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $asset = Asset::findOrFail($this->assetId);
        $tagIds = [];
        foreach ($this->tags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }
        $asset->tags()->sync($tagIds);
    }
}
