<?php

namespace App\Http\Controllers;

use App\Support\Content\NoteContent;
use App\Support\Content\PostContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostsController extends Controller
{
    public function __construct(private PostContent $posts, private NoteContent $notes) {}

    /**
     * Table of contents grouped by year, the two newest featured entries on top and a tag filter (?etiket=slug).
     */
    public function index(Request $request): View
    {
        $posts = collect($this->posts->all());
        $tags = collect($this->posts->tags());

        $activeTag = $request->string('etiket')->toString() ?: null;

        abort_if($activeTag !== null && ! $tags->contains('slug', $activeTag), 404);

        $featured = $activeTag ? collect() : $posts->where('isFeatured', true)->take(2)->values();

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
     * A single post with the newer/older neighbours, small notes on its tags and up to three related posts.
     */
    public function show(string $slug): View
    {
        $post = $this->posts->find($slug);

        abort_if($post === null, 404);

        [$newer, $older] = $this->posts->neighbours($post);

        return view('site.posts.show', [
            'post' => $this->posts->toArray($post),
            'postModel' => $post,
            'newer' => $newer,
            'older' => $older,
            'notes' => $this->notes->forPost($post),
            'related' => $this->posts->related($post),
        ]);
    }
}
