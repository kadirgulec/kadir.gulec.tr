<?php

namespace Database\Factories;

use App\Models\DevlogEntry;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevlogEntry>
 */
class DevlogEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loggable_type' => (new Project)->getMorphClass(),
            'loggable_id' => Project::factory(),
            'date' => fake()->dateTimeBetween('-1 year'),
            'body' => fake()->sentence(),
        ];
    }
}
