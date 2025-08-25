<?php

namespace Database\Seeders;

use App\Models\AssetView;
use Illuminate\Database\Seeder;

class AssetViewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AssetView::factory(150)->create();
    }
}
