<?php

namespace Database\Factories;

use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalVisibility;
use App\Models\Goal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kind' => GoalKind::LongTerm,
            'title' => rtrim(fake()->unique()->sentence(4), '.'),
            'visibility' => GoalVisibility::Public,
            'why' => fake()->sentence(),
            'started_year' => 2024,
        ];
    }

    public function longTerm(): static
    {
        return $this->state(fn (array $attributes) => ['kind' => GoalKind::LongTerm]);
    }

    /**
     * A chain whose days end today: x done, e excused, - missed (oldest first).
     */
    public function chain(string $pattern = 'xxxxx'): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => GoalKind::Chain,
            'why' => null,
            'started_year' => null,
            'started_on' => CarbonImmutable::today()->subDays(strlen($pattern) - 1),
        ])->afterCreating(function (Goal $goal) use ($pattern): void {
            $start = CarbonImmutable::today()->subDays(strlen($pattern) - 1);

            foreach (str_split($pattern) as $offset => $day) {
                if ($day !== '-') {
                    $goal->chainDays()->create(['date' => $start->addDays($offset), 'state' => $day === 'x' ? 'done' : 'excused']);
                }
            }
        });
    }

    public function yearly(GoalMeasure $measure = GoalMeasure::Numeric, ?int $year = null): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => GoalKind::Yearly,
            'why' => null,
            'started_year' => null,
            'year' => $year ?? CarbonImmutable::today()->year,
            'measure' => $measure,
            'target' => $measure === GoalMeasure::Numeric ? 12 : null,
            'unit' => $measure === GoalMeasure::Numeric ? 'kitap' : null,
        ]);
    }

    public function censored(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => GoalVisibility::Censored]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['visibility' => GoalVisibility::Hidden]);
    }
}
