<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'tagline' => fake()->sentence(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'started_year' => fake()->numberBetween(2018, 2026),
            'demo_url' => null,
            'repo_url' => 'https://github.com/kadirgulec/'.fake()->slug(2),
            'body' => "## Hangi problemi çözüyor?\n\n".fake()->paragraph(),
            'is_featured' => false,
            'published_at' => now()->subDay(),
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => now()->addWeek()]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }
}
