<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetView;
use App\Models\User;

class ViewService
{
    public function record(User $user, Asset $asset): AssetView
    {
        return AssetView::updateOrCreate(
            ['user_id' => $user->id, 'asset_id' => $asset->id],
            ['last_seen_at' => now()]
        );
    }

    public function getForAsset(Asset $asset)
    {
        return $asset->views()->with('user')->get();
    }

    public function getByUser(User $user)
    {
        return $user->assetViews()->with('asset')->get();
    }
}