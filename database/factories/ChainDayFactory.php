<?php

namespace Database\Factories;

use App\Models\ChainDay;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChainDay>
 */
class ChainDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory()->chain(''),
            'date' => now()->toDateString(),
            'state' => 'done',
        ];
    }
}
