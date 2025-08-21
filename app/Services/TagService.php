<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Tag;

class TagService
{
    public function sync(int $assetId, array $tags): void
    {
        $asset = Asset::findOrFail($assetId);
        $tagIds = [];
        foreach ($tags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }
        $asset->tags()->sync($tagIds);
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
