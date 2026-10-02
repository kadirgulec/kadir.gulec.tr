<?php

namespace App\Http\Controllers;

use App\Support\PrototypeContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostsController extends Controller
{
    /**
     * Table of contents grouped by year, featured entries on top and a tag filter (?etiket=slug).
     */
    public function index(Request $request): View
    {
        $posts = collect(PrototypeContent::posts());

        $tags = $posts
            ->flatMap(fn (array $post): array => array_combine($post['tagSlugs'], $post['tags']))
            ->unique()
            ->map(fn (string $tag, string $slug): array => [
                'name' => $tag,
                'slug' => $slug,
                'count' => $posts->filter(fn (array $post): bool => in_array($slug, $post['tagSlugs'], true))->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $activeTag = $request->string('etiket')->toString() ?: null;

        abort_if($activeTag !== null && ! $tags->contains('slug', $activeTag), 404);

        $featured = $activeTag ? collect() : $posts->where('isFeatured', true)->values();

        $listed = $posts
            ->when($activeTag, fn ($posts) => $posts->filter(fn (array $post): bool => in_array($activeTag, $post['tagSlugs'], true)))
            ->reject(fn (array $post): bool => $featured->contains('slug', $post['slug']));

        return view('site.posts.index', [
            'postCount' => $posts->count(),
            'tags' => $tags->all(),
            'activeTag' => $tags->firstWhere('slug', $activeTag),
            'featured' => $featured->all(),
            'postsByYear' => $listed->groupBy(fn (array $post): int => $post['publishedAt']->year)->all(),
        ]);
    }

    /**
     * A single post with the newer/older neighbours and up to three related posts.
     */
    public function show(string $slug): View
    {
        $posts = collect(PrototypeContent::posts())->values();
        $index = $posts->search(fn (array $post): bool => $post['slug'] === $slug);

        abort_if($index === false, 404);

        $post = $posts[$index];

        return view('site.posts.show', [
            'post' => $post,
            'newer' => $posts->get($index - 1),
            'older' => $posts->get($index + 1),
            'related' => $posts
                ->reject(fn (array $other): bool => $other['slug'] === $slug)
                ->filter(fn (array $other): bool => array_intersect($other['tagSlugs'], $post['tagSlugs']) !== [])
                ->take(3)
                ->values()
                ->all(),
        ]);
    }
}
