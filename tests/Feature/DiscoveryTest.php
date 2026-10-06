<?php

use App\Models\Goal;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Watchable;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

describe('meta tags', function () {
    it('describes a post and points at its preview image', function () {
        $post = Post::factory()->create(['title' => 'Bir yazı', 'meta_description' => 'Kısa açıklama.']);

        $this->get(route('posts.show', $post->slug))
            ->assertSee('<meta name="description" content="Kısa açıklama." />', false)
            ->assertSee('<meta property="og:type" content="article" />', false)
            ->assertSee('/og/post/'.$post->slug.'.png?v=', false)
            ->assertSee('<link rel="alternate" type="application/atom+xml"', false);
    });

    it('falls back to the excerpt for the description', function () {
        $post = Post::factory()->create(['excerpt' => null, 'body' => "İlk paragraf açıklama olur.\n\nİkincisi olmaz."]);

        $this->get(route('posts.show', $post->slug))->assertSee('content="İlk paragraf açıklama olur."', false);
    });
});

describe('preview images', function () {
    it('draws a PNG once and keeps it', function () {
        $post = Post::factory()->create();

        $this->get(route('og', ['kind' => 'post', 'key' => $post->slug]))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('og', ['kind' => 'post', 'key' => $post->slug]))->assertOk();

        expect(Storage::disk('public')->files('og'))->toHaveCount(1);
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get(Storage::disk('public')->files('og')[0]));
        expect([$width, $height])->toBe([1200, 630]);
    });

    it('has none for drafts, unknown kinds or hidden goals', function () {
        $this->get(route('og', ['kind' => 'post', 'key' => Post::factory()->draft()->create()->slug]))->assertNotFound();
        $this->get(route('og', ['kind' => 'project', 'key' => Project::factory()->draft()->create()->slug]))->assertNotFound();
        $this->get(route('og', ['kind' => 'film', 'key' => Watchable::factory()->draft()->create()->slug]))->assertNotFound();
        $this->get(route('og', ['kind' => 'user', 'key' => 'x']))->assertNotFound();

        $hidden = Goal::factory()->chain()->hidden()->create();
        $this->get(route('og', ['kind' => 'goal', 'key' => $hidden->slug]))->assertNotFound();
        $this->get(route('og', ['kind' => 'goal', 'key' => 'k-'.$hidden->id]))->assertNotFound();
    });

    it('answers a censored chain only under its opaque key', function () {
        $chain = Goal::factory()->chain()->censored()->create();

        $this->get(route('og', ['kind' => 'goal', 'key' => $chain->slug]))->assertNotFound();
        $this->get(route('og', ['kind' => 'goal', 'key' => 'k-'.$chain->id]))->assertOk();
    });

    it('draws a note as its post-it, and none for a draft note', function () {
        $note = Note::factory()->create();

        $this->get(route('notes.show', $note->id))->assertSee(route('og', ['kind' => 'note', 'key' => $note->id, 'v' => $note->updated_at->getTimestamp()]), false);
        $this->get(route('og', ['kind' => 'note', 'key' => $note->id]))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('og', ['kind' => 'note', 'key' => Note::factory()->draft()->create()->id]))->assertNotFound();
    });

    it('draws films and pages', function () {
        $this->get(route('og', ['kind' => 'film', 'key' => Watchable::factory()->create()->slug]))->assertOk();
        $this->get(route('og', ['kind' => 'page', 'key' => 'home']))->assertOk();
    });
});

describe('feed', function () {
    it('lists published posts with their full text and sidenotes as footnotes', function () {
        Post::factory()->create(['title' => 'Görünen', 'body' => "Metin[^1].\n\n[^1]: Kenar notu."]);
        Post::factory()->draft()->create(['title' => 'Taslak yazı']);

        $response = $this->get(route('posts.feed'))->assertOk()->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
        $feed = simplexml_load_string($response->getContent());

        expect($feed)->not->toBeFalse()
            ->and(count($feed->entry))->toBe(1)
            ->and((string) $feed->entry[0]->title)->toBe('Görünen')
            ->and((string) $feed->entry[0]->content)->toContain('class="footnotes"')->not->toContain('sidenote');
    });
});

describe('notes feed', function () {
    it('titles each published note with the start of its text and files it under its tag', function () {
        Note::factory()->for(Tag::factory()->state(['name' => 'javascript']))->create([
            'body' => '`structuredClone()` derin kopya için artık tarayıcılarda yerleşik geliyor; eski JSON hilesi tarihe karışabilir.',
        ]);
        Note::factory()->draft()->create(['body' => 'Taslak not.']);

        $response = $this->get(route('notes.feed'))->assertOk()->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
        $feed = simplexml_load_string($response->getContent());

        expect($feed)->not->toBeFalse()
            ->and(count($feed->entry))->toBe(1)
            ->and((string) $feed->entry[0]->title)->toBe('structuredClone() derin kopya için artık tarayıcılarda…')
            ->and((string) $feed->entry[0]->category['label'])->toBe('javascript')
            ->and((string) $feed->entry[0]->content)->toContain('<code class="inline-code">structuredClone()</code>');
    });
});

describe('sitemap and robots', function () {
    it('lists only what any visitor may read', function () {
        $post = Post::factory()->create();
        $draft = Post::factory()->draft()->create();
        $chain = Goal::factory()->chain()->create();
        $censored = Goal::factory()->chain()->censored()->create();
        $hidden = Goal::factory()->longTerm()->hidden()->create();
        $note = Note::factory()->create();
        $draftNote = Note::factory()->draft()->create();

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        expect(simplexml_load_string($xml))->not->toBeFalse()
            ->and($xml)->toContain(route('posts.show', $post->slug))
            ->toContain(route('goals.chain', $chain->slug))
            ->not->toContain($draft->slug)
            ->not->toContain($censored->slug)
            ->not->toContain($hidden->slug)
            ->toContain('<loc>'.route('notes.show', $note->id).'</loc>')
            ->not->toContain('<loc>'.route('notes.show', $draftNote->id).'</loc>');
    });

    it('keeps crawlers out of the admin and account pages', function () {
        $this->get('/robots.txt')->assertOk()
            ->assertSeeText('Disallow: /admin')
            ->assertSeeText('Disallow: /hesap')
            ->assertSeeText('Sitemap: '.route('sitemap'));
    });
});
