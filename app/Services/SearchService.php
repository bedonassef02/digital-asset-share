<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return Asset::where('user_id', auth()->id())
            ->where(function (Builder $queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->orWhereHas('tags', function (Builder $queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', '%' . $query . '%');
            })
            ->with('tags') // Eager load tags for display if needed
            ->get();
    }
}
