<?php

namespace Database\Factories;

use App\Enums\GoalMeasure;
use App\Models\Goal;
use App\Models\GoalMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalMilestone>
 */
class GoalMilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory()->yearly(GoalMeasure::Milestones),
            'title' => fake()->sentence(3),
            'done_at' => null,
            'sort_order' => 0,
        ];
    }
}
