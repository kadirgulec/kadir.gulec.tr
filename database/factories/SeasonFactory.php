<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'watchable_id' => Watchable::factory()->series(),
            'number' => 1,
            'episode_count' => 10,
            'rating' => null,
            'note' => null,
        ];
    }
}
