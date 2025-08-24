<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['info', 'success', 'warning', 'error']),
            'message' => $this->faker->sentence,
            'notifiable_type' => null, // Default to null
            'notifiable_id' => null,   // Default to null
            'read_at' => null,         // Default to unread
        ];
    }

    /**
     * Indicate that the notification is read.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function read()
    {
        return $this->state(function (array $attributes) {
            return [
                'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the notification is associated with a notifiable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $notifiable
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forNotifiable(\Illuminate\Database\Eloquent\Model $notifiable)
    {
        return $this->state(function (array $attributes) use ($notifiable) {
            return [
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id' => $notifiable->id,
            ];
        });
    }
}
