<?php

namespace Database\Factories;

use App\Models\MonthlyReview;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyReview>
 */
class MonthlyReviewFactory extends Factory
{
    public function definition(): array
    {
        $month = CarbonImmutable::today()->subMonth()->startOfMonth();

        return [
            'month' => $month,
            'summary' => fake()->sentence(),
            'score' => fake()->numberBetween(4, 9),
            'stats' => [
                'chains' => [],
                'yearly' => [],
                'published' => ['posts' => 0, 'notes' => 0, 'viewings' => 0],
                'visitors' => ['views' => 0, 'visits' => 0, 'topPost' => null],
            ],
            'published_at' => $month->addMonth()->addDays(2),
        ];
    }

    /**
     * The review of the month around $date (e.g. "2026-09").
     */
    public function forMonth(string $date): static
    {
        $month = CarbonImmutable::parse($date)->startOfMonth();

        return $this->state(fn (array $attributes) => ['month' => $month, 'published_at' => $month->addMonth()->addDays(2)]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }
}
