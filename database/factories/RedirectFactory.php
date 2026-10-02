<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_path' => '/projeler/'.fake()->unique()->slug(2),
            'to_path' => '/projeler/'.fake()->slug(2),
        ];
    }
}
