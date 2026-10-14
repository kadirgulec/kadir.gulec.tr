<?php

namespace Database\Factories;

use App\Enums\BookFormat;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->unique()->sentence(3), '.'),
            'original_title' => null,
            'author' => fake()->name(),
            'translator' => null,
            'publisher' => fake()->company(),
            'year' => fake()->numberBetween(1900, 2026),
            'language' => 'Türkçe',
            'format' => BookFormat::Ebook,
            'overview' => fake()->paragraph(),
            'rating' => fake()->randomElement([6.0, 7.5, 8.0, 9.5]),
            'is_favorite' => false,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }

    public function reviewed(string $review = 'Çok sevdim.'): static
    {
        return $this->state(fn (array $attributes) => ['review' => $review, 'review_published_at' => now()->subHour()]);
    }

    /**
     * Not read yet: no rating, not public, waiting on the reading list.
     */
    public function onReadingList(?string $note = null): static
    {
        return $this->state(fn (array $attributes) => ['rating' => null, 'published_at' => null])
            ->afterCreating(fn (Book $book) => $book->addToReadingList($note));
    }
}
