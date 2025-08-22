<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Share;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ShareService
{
    public function createShareLink(Asset $asset, array $data)
    {
        $token = Str::random(32);
        $password = isset($data['password']) ? Hash::make($data['password']) : null;

        $share = Share::create([
            'token' => $token,
            'asset_id' => $asset->id,
            'user_id' => auth()->id(),
            'expires_at' => isset($data['expires_at']) ? now()->parse($data['expires_at']) : null,
            'password' => $password,
        ]);

        return $share;
    }

    public function resolveShareLink(string $token, ?string $password = null)
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

    public function listUserShares()
    {
        return auth()->user()->shares;
    }

    public function revokeShareLink(string $token)
    {
        $share = Share::where('token', $token)->firstOrFail();

        if ($share->user_id !== auth()->id()) {
            abort(403, 'You are not authorized to revoke this share link.');
        }

        $share->delete();

        return true;
    }
}
