<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalProgress>
 */
class GoalProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory()->yearly(),
            'date' => now()->toDateString(),
            'amount' => 1,
            'note' => null,
        ];
    }
}
