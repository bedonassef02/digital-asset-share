<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            AssetSeeder::class,
            AssetVersionSeeder::class,
            CollectionSeeder::class,
            DownloadSeeder::class,
            AssetViewSeeder::class,
            ShareSeeder::class,
            NotificationSeeder::class,
            TagSeeder::class,
        ]);
    }
}