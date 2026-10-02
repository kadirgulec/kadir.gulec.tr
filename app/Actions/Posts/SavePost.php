<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a post with its tags.
 */
class SavePost
{
    /**
     * @param  array<string, mixed>  $attributes  Validated post columns.
     * @param  list<string>  $tagNames
     */
    public function handle(?Post $post, array $attributes, array $tagNames): Post
    {
        $post ??= new Post;

        DB::transaction(function () use ($post, $attributes, $tagNames): void {
            $post->fill($attributes)->save();
            $post->syncTagNames($tagNames);
        });

        return $post;
    }
}
