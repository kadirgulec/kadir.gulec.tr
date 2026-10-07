<?php

use App\Models\Post;

it('offers to share each section page under its own address', function (string $routeName) {
    $this->get(route($routeName))
        ->assertSee('data-share-url="'.route($routeName).'"', escape: false);
})->with([
    'home' => ['home'],
    'posts' => ['posts.index'],
    'notes' => ['notes.index'],
    'watched' => ['watched.index'],
    'goals' => ['goals.index'],
    'projects' => ['projects.index'],
    'about' => ['about'],
]);

it('shares a post under its own address', function () {
    $post = Post::factory()->create(['slug' => 'yeniden-cirak-olmak']);

    $this->get(route('posts.show', $post->slug))
        ->assertSee('data-share-url="'.route('posts.show', 'yeniden-cirak-olmak').'"', escape: false);
});

it('keeps the tag filter in the shared link but drops the page number and tracking parameters', function () {
    Post::factory()->create()->syncTagNames(['kariyer']);

    $this->get(route('posts.index', ['etiket' => 'kariyer', 'page' => 2, 'utm_source' => 'mastodon']))
        ->assertSee('data-share-url="'.route('posts.index').'?etiket=kariyer"', escape: false);
});
