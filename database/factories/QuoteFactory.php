<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotable_type' => 'book',
            'quotable_id' => Book::factory(),
            'body' => fake()->sentence(14),
            'said_by' => fake()->optional()->name(),
            'location' => '%'.fake()->numberBetween(1, 99),
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }
}
