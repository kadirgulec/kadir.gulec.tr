<?php

namespace Database\Factories;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A finished reading by default; the states put it on the shelf or leave it half-way.
 *
 * @extends Factory<Reading>
 */
class ReadingFactory extends Factory
{
    public function definition(): array
    {
        $finishedOn = fake()->dateTimeBetween('-1 year');

        return [
            'book_id' => Book::factory(),
            'status' => ReadingStatus::Finished,
            'started_on' => (clone $finishedOn)->modify('-3 weeks'),
            'finished_on' => $finishedOn,
            'progress' => 100,
        ];
    }

    public function reading(int $progress = 40): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReadingStatus::Reading, 'finished_on' => null, 'progress' => $progress]);
    }

    public function paused(int $progress = 60): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReadingStatus::Paused, 'finished_on' => null, 'progress' => $progress]);
    }

    public function abandoned(int $progress = 30): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReadingStatus::Abandoned, 'progress' => $progress]);
    }
}
