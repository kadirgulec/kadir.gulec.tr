<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\User;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->member(),
            'followable_type' => (new Watchable)->getMorphClass(),
            'followable_id' => Watchable::factory(),
        ];
    }
}
