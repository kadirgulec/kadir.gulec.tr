<?php

namespace Database\Seeders;

use App\Actions\Watched\StorePoster;
use App\Models\Post;
use App\Models\Watchable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * The sample content of the design prototype (posts, and in later steps
 * films and goals), so the site looks filled on a local machine.
 * DatabaseSeeder only calls it outside production.
 */
class DemoSeeder extends Seeder
{
    public function run(StorePoster $storePoster): void
    {
        $this->seedPosts();
        $this->seedWatched($storePoster);
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

    private function seedWatched(StorePoster $storePoster): void
    {
        /** @var list<array<string, mixed>> $entries */
        $entries = require __DIR__.'/data/demo-watched.php';

        foreach ($entries as $data) {
            if (Watchable::query()->where('type', $data['type'])->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $watchedOn = CarbonImmutable::parse($data['watched_on']);

            $watchable = Watchable::query()->create([
                'type' => $data['type'],
                'slug' => $data['slug'],
                'title' => $data['title'],
                'original_title' => $data['original_title'],
                'year' => $data['year'],
                'creator' => $data['creator'],
                'genres' => $data['genres'],
                'runtime_minutes' => $data['runtime_minutes'],
                'overview' => $data['overview'],
                'cast' => $data['cast'],
                'rating' => $data['rating'],
                'is_favorite' => $data['is_favorite'],
                'review' => $data['review'],
                'review_published_at' => $data['review'] !== null ? $watchedOn->addDay() : null,
                'series_status' => $data['series_status'],
                'current_season' => $data['current_season'],
                'current_episode' => $data['current_episode'],
                'published_at' => $watchedOn,
            ]);

            foreach ($data['seasons'] as $season) {
                $watchable->seasons()->create($season);
            }

            if ($data['is_rewatch']) {
                $watchable->viewings()->create(['watched_on' => $watchedOn->subYears(3), 'place' => 'Evde']);
            }

            $watchable->viewings()->create(['watched_on' => $watchedOn, 'place' => $data['place']]);

            $poster = public_path('images/prototype/posters/'.$data['poster_file']);

            if (is_file($poster)) {
                $storePoster->handle($watchable, $poster);
            } else {
                $watchable->forceFill(['accent' => $data['accent'], 'poster_colors' => $data['poster_colors']])->save();
            }
        }
    }
}
