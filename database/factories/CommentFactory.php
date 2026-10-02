<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commentable_type' => (new Post)->getMorphClass(),
            'commentable_id' => Post::factory(),
            'user_id' => User::factory()->member(),
            'body' => fake()->sentence(),
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['approved_at' => null]);
    }
}
