<?php

declare(strict_types=1);

namespace App\Services\View;

use App\Models\AssetView;
use Illuminate\Database\Eloquent\Model;

class ViewService
{
    public function record(int $userId, Model $viewable): AssetView
    {
        return $viewable->views()->updateOrCreate(
            ['user_id' => $userId],
            ['last_seen_at' => now()]
        );
    }

    public function getFor(Model $viewable): \Illuminate\Database\Eloquent\Collection
    {
        return $viewable->views()->with('user')->get();
    }
}
