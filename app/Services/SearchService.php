<?php

namespace App\Services;

use App\Models\AssetVersion;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return AssetVersion::whereHas('asset', function (Builder $queryBuilder) {
            $queryBuilder->where('user_id', auth()->id());
        })
        ->where(function (Builder $queryBuilder) use ($query) {
            $queryBuilder->where('name', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%');
        })
        ->orWhereHas('asset.tags', function (Builder $queryBuilder) use ($query) {
            $queryBuilder->where('name', 'like', '%' . $query . '%');
        })
        ->with('asset.tags')
        ->get();
    }
}
