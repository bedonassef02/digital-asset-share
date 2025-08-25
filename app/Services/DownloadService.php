<?php

namespace App\Services;

use App\Models\Download;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class DownloadService
{
    public function record(Authenticatable $user, Model $downloadable): Download
    {
        return $downloadable->downloads()->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    public function getFor(Model $downloadable)
    {
        return $downloadable->downloads()->with('user')->get();
    }
}
