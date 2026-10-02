<?php

namespace Database\Factories;

use App\Enums\ToolboxGroup;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technology>
 */
class TechnologyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Laravel', 'Livewire', 'Alpine.js', 'MySQL', 'Tailwind CSS', 'PHP', 'JavaScript', 'Python', 'Java', 'Vue', 'Redis', 'Docker']).' '.fake()->unique()->numberBetween(1, 9999),
            'toolbox_group' => null,
            'toolbox_order' => 0,
        ];
    }

    public function inToolbox(ToolboxGroup $group = ToolboxGroup::Daily): static
    {
        return $this->state(fn (array $attributes) => ['toolbox_group' => $group]);
    }
}
