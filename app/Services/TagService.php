<?php

namespace App\Services;

use App\Jobs\SyncAssetTags;

class TagService
{
    public function sync(int $assetId, array $tags): void
    {
        SyncAssetTags::dispatch($assetId, $tags);
    }

    public function detach(int $assetId, array $tags): void
    {
        $asset = Asset::findOrFail($assetId);
        $tagIds = [];
        foreach ($tags as $tagName) {
            $tag = Tag::where('name', $tagName)->first();
            if ($tag) {
                $tagIds[] = $tag->id;
            }
        }
        $asset->tags()->detach($tagIds);
    }
}
