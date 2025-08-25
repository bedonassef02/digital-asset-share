<?php

namespace Database\Factories;

use App\Models\Download;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Download::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'downloadable_id' => $this->faker->randomNumber(),
            'downloadable_type' => $this->faker->randomElement(['App\\Models\\Asset', 'App\\Models\\Collection']),
        ];
    }
}
