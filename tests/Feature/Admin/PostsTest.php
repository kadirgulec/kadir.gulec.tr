<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\Images\ImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

describe('editor', function () {
    it('creates a draft with tags, reading time and rendered Markdown', function () {
        Livewire::test('pages::admin.posts.edit')
            ->set('form.title', 'Livewire ile ilk adım')
            ->set('form.body', str_repeat('kelime ', 450)."\n\nBir ==vurgu==.")
            ->set('form.tagNames', ['livewire', 'kod'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $post = Post::sole();
        expect($post->slug)->toBe('livewire-ile-ilk-adim')
            ->and($post->reading_minutes)->toBe(3)
            ->and($post->published_at)->toBeNull()
            ->and($post->body_html)->toContain('<mark class="marker">vurgu</mark>')
            ->and($post->tags->pluck('name')->all())->toBe(['livewire', 'kod']);
    });

    it('requires a title', function () {
        Livewire::test('pages::admin.posts.edit')
            ->call('save')
            ->assertHasErrors(['form.title' => 'required']);
    });

    it('rejects images without a description', function (string $body) {
        Livewire::test('pages::admin.posts.edit')
            ->set('form.title', 'Görselli yazı')
            ->set('form.body', $body)
            ->call('save')
            ->assertHasErrors('form.body');
    })->with([
        'empty alt' => ['![](/storage/posts/a-960.webp)'],
        'placeholder alt' => ['![açıklama yaz](/storage/posts/a-960.webp)'],
    ]);

    it('uses the first paragraph as the excerpt when none is written', function () {
        $post = Post::factory()->create(['excerpt' => null, 'body' => "İlk paragraf burada.\n\nİkinci paragraf."]);

        expect($post->excerptText())->toBe('İlk paragraf burada.');
    });

    it('stores an in-text image and hands the Markdown to the editor', function () {
        Storage::fake('public');

        Livewire::test('pages::admin.posts.edit', ['post' => Post::factory()->create()])
            ->set('bodyImage', UploadedFile::fake()->image('foto.jpg', 800, 600))
            ->assertHasNoErrors()
            ->assertDispatched('markdown-insert', fn (string $name, array $params): bool => str_starts_with($params['text'], '![açıklama yaz](/storage/posts/'));

        expect(Storage::disk('public')->allFiles('posts'))->toHaveCount(count(ImageStore::WIDTHS));
    });

    it('creates a redirect when the slug of a published post changes', function () {
        $post = Post::factory()->create(['slug' => 'eski-baslik']);

        Livewire::test('pages::admin.posts.edit', ['post' => $post])
            ->set('form.slug', 'yeni-baslik')
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/yazilar/eski-baslik')->assertRedirect('/yazilar/yeni-baslik');
    });

    it('does not create a redirect for a draft', function () {
        $post = Post::factory()->draft()->create(['slug' => 'taslak-ad']);

        Livewire::test('pages::admin.posts.edit', ['post' => $post])->set('form.slug', 'baska-ad')->call('save');

        $this->get('/yazilar/taslak-ad')->assertNotFound();
    });
});

describe('list', function () {
    it('filters posts by publication state', function () {
        Post::factory()->draft()->create(['title' => 'Taslaktaki yazı']);
        Post::factory()->create(['title' => 'Yayındaki yazı']);

        Livewire::test('pages::admin.posts.index')
            ->set('state', 'draft')
            ->assertSee('Taslaktaki yazı')
            ->assertDontSee('Yayındaki yazı');
    });
});

describe('tags', function () {
    it('renames a tag', function () {
        $tag = Tag::factory()->create(['name' => 'larvel']);

        Livewire::test('pages::admin.tags.index')
            ->call('edit', $tag->id)
            ->set('name', 'laravel')
            ->call('rename')
            ->assertHasNoErrors();

        expect($tag->fresh()->slug)->toBe('laravel');
    });

    it('merges a tag into another and keeps the posts', function () {
        $post = Post::factory()->create();
        $post->syncTagNames(['js']);
        $target = Tag::factory()->create(['name' => 'javascript']);
        $source = Tag::query()->where('name', 'js')->sole();

        Livewire::test('pages::admin.tags.index')
            ->call('startMerge', $source->id)
            ->set('mergeTarget', (string) $target->id)
            ->call('merge')
            ->assertHasNoErrors();

        $this->assertModelMissing($source);
        expect($post->fresh()->tags->pluck('name')->all())->toBe(['javascript']);
    });

    it('does not merge a tag into itself', function () {
        $tag = Tag::factory()->create();

        Livewire::test('pages::admin.tags.index')
            ->call('startMerge', $tag->id)
            ->set('mergeTarget', (string) $tag->id)
            ->call('merge')
            ->assertHasErrors('mergeTarget');
    });
});
