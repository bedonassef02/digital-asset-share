<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneDeletedAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:prune-deleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete assets that were soft-deleted more than 30 days ago.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = Carbon::now()->subDays(30);

        $assetsToPrune = Asset::onlyTrashed()
            ->where('deleted_at', '<=', $threshold)
            ->get();

        $count = 0;
        foreach ($assetsToPrune as $asset) {
            // Use the forceDelete method from AssetService if available, or directly forceDelete
            // For now, directly forceDelete as AssetService is not directly accessible here
            $asset->forceDelete();
            $count++;
        }

        $this->info("{$count} assets permanently deleted.");
    }
}
