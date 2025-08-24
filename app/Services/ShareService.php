<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Share;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ShareService
{
    public function create(Asset $asset, array $data, int $userId)
    {
        $token = Str::random(32);
        $password = isset($data['password']) ? Hash::make($data['password']) : null;

        $share = Share::create([
            'token' => $token,
            'asset_id' => $asset->id,
            'user_id' => $userId,
            'expires_at' => isset($data['expires_at']) ? now()->parse($data['expires_at']) : null,
            'password' => $password,
        ]);

        return $share;
    }

    public function resolve(string $token, ?string $password = null)
    {
        $share = Share::where('token', $token)->firstOrFail();

        if ($share->expires_at && $share->expires_at->isPast()) {
            abort(403, 'Share link has expired.');
        }

        if ($share->password && (! $password || ! Hash::check($password, $share->password))) {
            abort(403, 'Incorrect password.');
        }

        return $share->asset;
    }

    public function list(int $userId)
    {
        return Share::where('user_id', $userId)->get();
    }

    public function revoke(string $token, int $userId)
    {
        $share = Share::where('token', $token)->firstOrFail();

        if ($share->user_id !== $userId) {
            abort(403, 'You are not authorized to revoke this share link.');
        }

        $share->delete();

        return true;
    }
}
