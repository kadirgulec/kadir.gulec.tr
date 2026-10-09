<?php

namespace Database\Factories;

use App\Enums\ReviewItemKind;
use App\Models\MonthlyReview;
use App\Models\ReviewItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewItem>
 */
class ReviewItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'monthly_review_id' => MonthlyReview::factory(),
            'kind' => ReviewItemKind::Good,
            'body' => rtrim(fake()->sentence(5), '.'),
            'sort_order' => 0,
        ];
    }

    public function hard(): static
    {
        return $this->state(fn (array $attributes) => ['kind' => ReviewItemKind::Hard]);
    }

    public function toTry(): static
    {
        return $this->state(fn (array $attributes) => ['kind' => ReviewItemKind::Try]);
    }
}
