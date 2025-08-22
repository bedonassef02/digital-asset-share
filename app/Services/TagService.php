<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Tag;

class TagService
{
    public function replaceAll(int $assetId, array $tags): void
    {
        $asset = Asset::findOrFail($assetId);
        $tagIds = [];
        foreach ($tags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }
        $asset->tags()->sync($tagIds);
    }

    public function add(int $assetId, string $tagName): void
    {
        $asset = Asset::findOrFail($assetId);
        $tag = Tag::firstOrCreate(['name' => $tagName]);
        $asset->tags()->syncWithoutDetaching([$tag->id]);
    }

    public function remove(int $assetId, string $tagName): void
    {
        $asset = Asset::findOrFail($assetId);
        if ($tag = Tag::where('name', $tagName)->first()) {
            $asset->tags()->detach($tag->id);
        }
    }
}
