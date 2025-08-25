<?php

namespace Database\Factories;

use App\Models\AssetView;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetViewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetView::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'viewable_id' => $this->faker->randomNumber(),
            'viewable_type' => $this->faker->randomElement(['App\\Models\\Asset', 'App\\Models\\Collection']),
        ];
    }
}
