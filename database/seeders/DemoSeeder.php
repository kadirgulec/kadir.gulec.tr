<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * The sample content of the design prototype (posts, and in later steps
 * films and goals), so the site looks filled on a local machine.
 * DatabaseSeeder only calls it outside production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPosts();
    }

    private function seedPosts(): void
    {
        /** @var list<array{title: string, slug: string, excerpt: string, published_at: string, tags: list<string>, is_featured: bool, body: string}> $posts */
        $posts = require __DIR__.'/data/demo-posts.php';

        foreach ($posts as $data) {
            if (Post::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $post = Post::query()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'],
                'body' => $data['body'],
                'is_featured' => $data['is_featured'],
                'published_at' => $data['published_at'],
            ]);

            $post->syncTagNames($data['tags']);
        }
    }
}
