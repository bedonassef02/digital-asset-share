<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'asset_id' => Asset::factory(),
            'version' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->word.'.'.$this->faker->fileExtension(),
            'mime_type' => $this->faker->mimeType(),
            'size' => $this->faker->numberBetween(1000, 1000000),
            'extension' => $this->faker->fileExtension(),
            'description' => $this->faker->sentence(),
        ];
    }
}
