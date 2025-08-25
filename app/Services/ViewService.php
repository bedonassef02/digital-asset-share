<?php

namespace App\Services;

use App\Models\AssetView;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class ViewService
{
    public function record(Authenticatable $user, Model $viewable): AssetView
    {
        return $viewable->views()->updateOrCreate(
            ['user_id' => $user->getAuthIdentifier()],
            ['last_seen_at' => now()]
        );
    }

    public function getFor(Model $viewable)
    {
        return $viewable->views()->with('user')->get();
    }

    public function getByUser(Authenticatable $user)
    {
        return AssetView::where('user_id', $user->getAuthIdentifier())->with('viewable')->get();
    }
}
