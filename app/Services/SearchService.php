<?php

namespace App\Services;

use App\Models\AssetVersion;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function search(string $query, int $userId, ?string $mimeType = null, ?int $minSize = null, ?int $maxSize = null, ?string $startDate = null, ?string $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $results = AssetVersion::whereHas('asset', function (Builder $assetQuery) use ($userId) {
            $assetQuery->where('user_id', $userId);
        })
        ->where(function (Builder $versionQuery) use ($query) {
            $versionQuery->where('name', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->orWhereHas('asset.tags', function (Builder $tagQuery) use ($query) {
                    $tagQuery->where('name', 'like', '%' . $query . '%');
                });
        });

        if ($mimeType) {
            $results->where('mime_type', 'like', $mimeType . '%');
        }

        if ($minSize) {
            $results->where('size', '>=', $minSize);
        }

        if ($maxSize) {
            $results->where('size', '<=', $maxSize);
        }

        if ($startDate) {
            $results->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $results->whereDate('created_at', '<=', $endDate);
        }

        return $results->with('asset.tags')->get();
    }
}
