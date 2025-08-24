<?php

namespace App\Console\Commands;

use App\Models\Share;
use Illuminate\Console\Command;

class RemoveExpiredShares extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shares:remove-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes expired share links from the database.';

    /**
     * Execute the console command.
     */
    public function handle(
        \App\Services\NotificationService $notificationService
    ) {
        $expiredShares = Share::with('user', 'asset.latestVersion')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = $expiredShares->count();

        if ($count > 0) {
            foreach ($expiredShares as $share) {
                $notificationService->createNotification(
                    $share->user_id,
                    'info',
                    'Your shared link for "' . ($share->asset->latestVersion->name ?? '[Asset Name]') . '" has expired and been removed.',
                    $share->asset
                );
            }

            Share::whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->delete();
            $this->info("Successfully removed {$count} expired share links.");
        } else {
            $this->info("No expired share links found.");
        }
    }
}
