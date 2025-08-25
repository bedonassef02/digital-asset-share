<?php

namespace Database\Factories;

use App\Models\Share;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Share::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'shareable_id' => $this->faker->randomElement([
                \App\Models\Asset::factory(),
                \App\Models\Collection::factory(),
            ]),
            'shareable_type' => $this->faker->randomElement([
                'App\\Models\\Asset',
                'App\\Models\\Collection',
            ]),
            'user_id' => \App\Models\User::factory(),
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
            'password' => null,
        ];
    }
}