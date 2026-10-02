<?php

namespace Database\Factories;

use App\Models\Viewing;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Viewing>
 */
class ViewingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'watchable_id' => Watchable::factory(),
            'watched_on' => fake()->dateTimeBetween('-1 year'),
            'place' => fake()->randomElement(['Sinemada', 'Netflix, evde', 'Mubi']),
            'note' => null,
        ];
    }
}
