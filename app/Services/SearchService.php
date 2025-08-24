<?php

namespace App\Services;

use App\Models\AssetVersion;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function search(string $query, int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return AssetVersion::whereHas('asset', function (Builder $assetQuery) use ($userId) {
            $assetQuery->where('user_id', $userId);
        })
        ->where(function (Builder $versionQuery) use ($query) {
            $versionQuery->where('name', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->orWhereHas('asset.tags', function (Builder $tagQuery) use ($query) {
                    $tagQuery->where('name', 'like', '%' . $query . '%');
                });
        })
        ->with('asset.tags')
        ->get();
    }
}
