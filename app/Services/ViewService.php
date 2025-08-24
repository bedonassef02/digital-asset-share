<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetView;
use Illuminate\Contracts\Auth\Authenticatable;

class ViewService
{
    public function record(Authenticatable $user, Asset $asset): AssetView
    {
        return AssetView::updateOrCreate(
            ['user_id' => $user->getAuthIdentifier(), 'asset_id' => $asset->id],
            ['last_seen_at' => now()]
        );
    }

    public function getForAsset(Asset $asset)
    {
        return $asset->views()->with('user')->get();
    }

    public function getByUser(Authenticatable $user)
    {
        return $user->assetViews()->with('asset')->get();
    }
}