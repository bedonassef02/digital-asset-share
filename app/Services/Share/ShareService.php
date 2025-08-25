<?php

declare(strict_types=1);

namespace App\Services\Share;

use App\Models\Share;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShareService
{
    public function __construct(
        private ViewService $viewService,
        private DownloadService $downloadService
    ) {}

    public function create(Model $shareable, array $data, int $userId): Share
    {
        Gate::authorize('share', $shareable);

        $token = Str::random(32);
        $password = isset($data['password']) ? Hash::make($data['password']) : null;

        $share = $shareable->shares()->create([
            'token' => $token,
            'user_id' => $userId,
            'expires_at' => isset($data['expires_at']) ? now()->parse($data['expires_at']) : null,
            'password' => $password,
        ]);

        return $share;
    }

    public function resolve(string $token, int $userId, ?string $password = null): \Illuminate\Database\Eloquent\Model
    {
        $share = Share::where('token', $token)->firstOrFail();

        if ($share->expires_at && $share->expires_at->isPast()) {
            abort(403, 'Share link has expired.');
        }

        if ($share->password && (! $password || ! Hash::check($password, $share->password))) {
            abort(403, 'Incorrect password.');
        }

        $this->viewService->record($userId, $share->shareable);

        return $share->shareable;
    }

    public function list(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Share::where('user_id', $userId)->get();
    }

    public function revoke(string $token, int $userId): bool
    {
        $share = Share::where('token', $token)->firstOrFail();

        if ($share->user_id !== $userId) {
            abort(403, 'You are not authorized to revoke this share link.');
        }

        $share->delete();

        return true;
    }

    public function getStats(string $token): array
    {
        $share = Share::where('token', $token)->firstOrFail();
        Gate::authorize('view', $share);

        $views = $this->viewService->getFor($share->shareable);
        $downloads = $this->downloadService->getFor($share->shareable);

        return [
            'views' => $views,
            'view_count' => $views->count(),
            'downloads' => $downloads,
            'download_count' => $downloads->count(),
        ];
    }
}
