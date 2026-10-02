<?php

use App\Models\Post;
use App\Models\User;

/**
 * @param  list<string>  $tags
 */
function postWithTags(string $title, array $tags, string $publishedAt, array $attributes = []): Post
{
    $post = Post::factory()->create(['title' => $title, 'published_at' => $publishedAt, ...$attributes]);
    $post->syncTagNames($tags);

    return $post;
}

describe('index', function () {
    it('shows the two newest featured posts above the table of contents grouped by year', function () {
        postWithTags('Eski öne çıkan', ['kariyer'], '2024-03-01', ['is_featured' => true]);
        postWithTags('Yeni öne çıkan', ['kod'], '2026-05-01', ['is_featured' => true]);
        postWithTags('Orta öne çıkan', ['kod'], '2025-05-01', ['is_featured' => true]);
        postWithTags('Sıradan yazı', ['kod'], '2026-01-10');

        $this->get(route('posts.index'))
            ->assertSeeTextInOrder(['Öne çıkanlar', 'Yeni öne çıkan', 'Orta öne çıkan', 'Fihrist', '2026', 'Sıradan yazı', '2024', 'Eski öne çıkan']);
    });

    it('filters the list by tag', function () {
        postWithTags('Yeniden çırak olmak', ['kariyer'], '2025-01-01');
        postWithTags('Alpine.js ile 40 satırda', ['kod'], '2025-02-01');

        $this->get(route('posts.index', ['etiket' => 'kariyer']))
            ->assertSeeText('#kariyer etiketli yazılar')
            ->assertSeeText('Yeniden çırak olmak')
            ->assertDontSeeText('Alpine.js ile 40 satırda');
    });

    it('returns 404 for an unknown tag', function () {
        $this->get(route('posts.index', ['etiket' => 'olmayan-etiket']))->assertNotFound();
    });

    it('leaves drafts and scheduled posts out until their time comes', function () {
        Post::factory()->draft()->create(['title' => 'Taslak yazı']);
        $scheduled = Post::factory()->create(['title' => 'Zamanlanmış yazı', 'published_at' => now()->addHour()]);

        $this->get(route('posts.index'))->assertDontSeeText('Taslak yazı')->assertDontSeeText('Zamanlanmış yazı');

        $this->travelTo($scheduled->published_at->addMinute());
        $this->get(route('posts.index'))->assertSeeText('Zamanlanmış yazı');
    });
});

describe('show', function () {
    it('renders the Markdown body with highlights, sidenotes and a colored code card', function () {
        $post = Post::factory()->create(['body' => "Bir ==vurgu== ve not[^1].\n\n```php\n\$x = 1;\n```\n\n[^1]: Kenar notu.\n"]);

        $this->get(route('posts.show', $post->slug))
            ->assertSee('<mark class="marker">vurgu</mark>', false)
            ->assertSee('class="sidenote"', false)
            ->assertSee('data-code-block', false)
            ->assertSee('<span class="hl-variable">$x</span>', false);
    });

    it('links to the older post and back from it to the newer one', function () {
        $older = Post::factory()->create(['title' => 'Eski yazı', 'published_at' => '2026-01-01']);
        $newer = Post::factory()->create(['title' => 'Yeni yazı', 'published_at' => '2026-02-01']);

        $this->get(route('posts.show', $newer->slug))->assertSeeTextInOrder(['daha eski', 'Eski yazı'])->assertDontSeeText('daha yeni');
        $this->get(route('posts.show', $older->slug))->assertSeeTextInOrder(['daha yeni', 'Yeni yazı'])->assertDontSeeText('daha eski');
    });

    it('lists the posts sharing the most tags as related, newest first on a tie', function () {
        $post = postWithTags('Ana yazı', ['laravel', 'livewire', 'kod'], '2026-06-01');
        postWithTags('İki ortak etiket', ['laravel', 'livewire'], '2025-01-01');
        postWithTags('Bir ortak, yeni', ['kod'], '2026-03-01');
        postWithTags('Bir ortak, eski', ['laravel'], '2024-03-01');
        postWithTags('Ortak yok', ['kariyer'], '2023-04-01');

        $this->get(route('posts.show', $post->slug))
            ->assertSeeTextInOrder(['İlgili yazılar', 'İki ortak etiket', 'Bir ortak, yeni', 'Bir ortak, eski'])
            ->assertDontSeeText('Ortak yok');
    });

    it('returns 404 for an unknown post', function () {
        $this->get(route('posts.show', 'olmayan-yazi'))->assertNotFound();
    });

    it('shows a draft only to the admin, with a banner', function () {
        $draft = Post::factory()->draft()->create();

        $this->get(route('posts.show', $draft->slug))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('posts.show', $draft->slug))
            ->assertOk()
            ->assertSeeText('Taslak · sadece sen görüyorsun');
    });
});
