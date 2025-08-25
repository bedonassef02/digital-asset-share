<?php

namespace Database\Seeders;

use App\Models\AssetVersion;
use Illuminate\Database\Seeder;

class AssetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AssetVersion::factory(200)->create(); // Create more versions than assets
    }
}
