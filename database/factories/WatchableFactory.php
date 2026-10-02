<?php

namespace Database\Factories;

use App\Enums\SeriesStatus;
use App\Enums\WatchableType;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Watchable>
 */
class WatchableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => WatchableType::Film,
            'title' => rtrim(fake()->unique()->sentence(3), '.'),
            'original_title' => null,
            'year' => fake()->numberBetween(1970, 2026),
            'creator' => fake()->name(),
            'genres' => ['Dram'],
            'runtime_minutes' => fake()->numberBetween(80, 180),
            'overview' => fake()->paragraph(),
            'cast' => [['name' => fake()->name(), 'role' => fake()->firstName()]],
            'rating' => fake()->randomElement([6.0, 7.5, 8.0, 9.5]),
            'is_favorite' => false,
            'published_at' => now()->subDay(),
        ];
    }

    public function series(SeriesStatus $status = SeriesStatus::Watching): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WatchableType::Series,
            'runtime_minutes' => null,
            'series_status' => $status,
            'current_season' => 1,
            'current_episode' => 3,
            'rating' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }

    public function reviewed(string $review = 'Çok sevdim.'): static
    {
        return $this->state(fn (array $attributes) => ['review' => $review, 'review_published_at' => now()->subHour()]);
    }
}
