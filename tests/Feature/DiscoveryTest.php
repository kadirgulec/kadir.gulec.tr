<?php

use App\Models\Book;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\ReviewItem;
use App\Models\Tag;
use App\Models\Watchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(fn () => Storage::fake('public'));

/**
 * The page's JSON-LD nodes by their type.
 *
 * @return array<string, array<string, mixed>>
 */
function structuredData(TestResponse $response): array
{
    preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $response->getContent(), $match);

    return collect(json_decode($match[1] ?? '{}', true)['@graph'] ?? [])->keyBy('@type')->all();
}

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

    it('describes a book or film without an overview by its review, else by its facts', function () {
        $reviewed = Book::factory()->reviewed('Savaşın saçmalığını gülerek anlatıyor.')->create(['overview' => '', 'meta_description' => null]);
        $bare = Book::factory()->create(['title' => 'Aslan Asker Şvayk', 'author' => 'Jaroslav Hašek', 'overview' => null, 'meta_description' => '']);
        $film = Watchable::factory()->create(['title' => 'Arrival', 'year' => 2016, 'creator' => 'Denis Villeneuve', 'overview' => null]);

        $this->get(route('books.show', $reviewed->slug))->assertSee('<meta name="description" content="Savaşın saçmalığını gülerek anlatıyor." />', false);
        $this->get(route('books.show', $bare->slug))
            ->assertSee('<meta name="description" content="Kadir&#039;in okuduklarından bir kitap: Aslan Asker Şvayk, Jaroslav Hašek." />', false);
        $this->get(url($film->publicPath()))
            ->assertSee('<meta property="og:description" content="Kadir&#039;in izlediklerinden bir film: Arrival (2016), Denis Villeneuve." />', false);
    });

    it('lets search show large previews and a favicon it can read, on indexed pages only', function () {
        $this->get(route('about'))
            ->assertSee('<meta name="robots" content="max-image-preview:large" />', false)
            ->assertSee('<link rel="icon" href="/icons/icon-192.png" type="image/png" sizes="192x192">', false);

        $this->get(route('privacy'))->assertDontSee('max-image-preview', false);
    });

    it('fetches the poster of a film page first instead of lazily', function () {
        $film = Watchable::factory()->create(['title' => 'Arrival', 'poster_path' => 'posters/arrival.webp']);

        expect($this->get(url($film->publicPath()))->getContent())
            ->toMatch('/alt="Arrival afişi"[^>]*fetchpriority="high"/u')
            ->not->toMatch('/alt="Arrival afişi"[^>]*loading="lazy"/u');
    });

    it('never sends an empty description', function () {
        $goal = Goal::factory()->longTerm()->create(['why' => null]);

        $this->get(route('goals.show', $goal->slug))->assertDontSee('content=""', false);
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

    it('draws films, books and pages', function () {
        $this->get(route('og', ['kind' => 'film', 'key' => Watchable::factory()->create()->slug]))->assertOk();
        $this->get(route('og', ['kind' => 'kitap', 'key' => Book::factory()->create()->slug]))->assertOk();
        $this->get(route('og', ['kind' => 'kitap', 'key' => Book::factory()->draft()->create()->slug]))->assertNotFound();
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

describe('monthly reviews', function () {
    it('point at a preview image with the month and the score, drawn only when published', function () {
        $review = MonthlyReview::factory()->forMonth('2026-09')->create(['score' => 8]);
        $draft = MonthlyReview::factory()->forMonth('2026-08')->draft()->create();

        $this->get(route('goals.reviews.show', '2026-09'))->assertSee('/og/aylik/2026-09.png?v='.$review->updated_at->getTimestamp(), false);
        $this->get(route('og', ['kind' => 'aylik', 'key' => '2026-09']))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('og', ['kind' => 'aylik', 'key' => $draft->monthKey()]))->assertNotFound();
        $this->get(route('og', ['kind' => 'aylik', 'key' => '2026-13']))->assertNotFound();
    });

    it('have a feed of the published months with their lists', function () {
        $review = MonthlyReview::factory()->forMonth('2026-09')->create(['summary' => 'Spor oturdu.', 'score' => 8]);
        ReviewItem::factory()->for($review, 'review')->hard()->create(['body' => 'Az yazı']);
        MonthlyReview::factory()->forMonth('2026-08')->draft()->create();

        $response = $this->get(route('goals.reviews.feed'))->assertOk()->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
        $feed = simplexml_load_string($response->getContent());

        expect($feed)->not->toBeFalse()
            ->and(count($feed->entry))->toBe(1)
            ->and((string) $feed->entry[0]->title)->toBe('Eylül 2026 değerlendirmesi')
            ->and((string) $feed->entry[0]->content)->toContain('<p>Spor oturdu.</p>')
            ->toContain('Ayın puanı: 8 / 10')
            ->toContain('<h3>Zorlandığım</h3><ul><li><p>Az yazı</p></li></ul>');
    });

    it('are in the sitemap once published', function () {
        MonthlyReview::factory()->forMonth('2026-09')->create();
        MonthlyReview::factory()->forMonth('2026-08')->draft()->create();

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        expect($xml)->toContain(route('goals.reviews.show', '2026-09'))->not->toContain('2026-08')
            ->toContain('<loc>'.route('goals.reviews.index').'</loc>');
    });
});

describe('sitemap and robots', function () {
    it('lists only what any visitor may read', function () {
        $post = Post::factory()->create();
        $draft = Post::factory()->draft()->create();
        $goal = Goal::factory()->longTerm()->create();
        $hidden = Goal::factory()->longTerm()->hidden()->create();
        $note = Note::factory()->create();
        $draftNote = Note::factory()->draft()->create();

        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        expect(simplexml_load_string($xml))->not->toBeFalse()
            ->and($xml)->toContain(route('posts.show', $post->slug))
            ->toContain(route('goals.show', $goal->slug))
            ->not->toContain($draft->slug)
            ->not->toContain($hidden->slug)
            ->toContain('<loc>'.route('notes.show', $note->id).'</loc>')
            ->not->toContain('<loc>'.route('notes.show', $draftNote->id).'</loc>');
    });

    it('lists the shelf, both of its lists and every published book', function () {
        $book = Book::factory()->create();
        $draft = Book::factory()->draft()->onReadingList()->create();

        $xml = $this->get(route('sitemap'))->getContent();

        expect($xml)->toContain('<loc>'.route('shelf.index').'</loc>')
            ->toContain('<loc>'.route('watched.index').'</loc>')
            ->toContain('<loc>'.route('books.index').'</loc>')
            ->toContain('<loc>'.route('books.show', $book->slug).'</loc>')
            ->not->toContain($draft->slug);
    });

    it('leaves out the noindex pages: chains and the legal pages', function () {
        $chain = Goal::factory()->chain()->create();

        $xml = $this->get(route('sitemap'))->getContent();

        expect($xml)->not->toContain($chain->slug)
            ->not->toContain(route('privacy'))
            ->not->toContain(route('imprint'));
    });

    it('dates a section by its newest entry', function () {
        Post::factory()->create(['updated_at' => '2026-09-01 10:00:00']);
        Post::factory()->create(['updated_at' => '2026-09-20 10:00:00']);

        $lastmod = [];
        foreach (simplexml_load_string($this->get(route('sitemap'))->getContent())->url as $url) {
            $lastmod[(string) $url->loc] = (string) $url->lastmod;
        }

        expect($lastmod[route('posts.index')])->toStartWith('2026-09-20')
            ->and($lastmod[route('home')])->toStartWith('2026-09-20')
            ->and($lastmod[route('about')])->toBe('');
    });

    it('keeps crawlers out of the admin and account pages', function () {
        $this->get('/robots.txt')->assertOk()
            ->assertSeeText('Disallow: /admin')
            ->assertSeeText('Disallow: /hesap')
            ->assertSeeText('Sitemap: '.route('sitemap'));
    });

    it('lets crawlers reach the sign-in pages so they can see their noindex', function () {
        $this->get('/robots.txt')
            ->assertDontSeeText('Disallow: /login')
            ->assertDontSeeText('Disallow: /register')
            ->assertDontSeeText('Disallow: /forgot-password')
            ->assertSeeText('Disallow: /reset-password');

        foreach ([route('login'), route('register'), route('password.request')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<meta name="robots" content="noindex, nofollow" />', false)
                ->assertDontSee('application/ld+json', false);
        }
    });
});

describe('structured data', function () {
    it('describes a post as a blog post by Kadir, with its dates and the way back to the list', function () {
        $post = Post::factory()->create(['title' => 'Bir yazı', 'published_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-05 12:00:00']);

        $response = $this->get(route('posts.show', $post->slug))
            ->assertSee('<time datetime="2026-09-01T10:00:00+02:00">', false)
            ->assertSee('<meta property="article:published_time" content="2026-09-01T10:00:00+02:00" />', false)
            ->assertSee('<meta property="article:modified_time" content="2026-09-05T12:00:00+02:00" />', false);
        $data = structuredData($response);

        expect($data['BlogPosting'])->toMatchArray([
            'headline' => 'Bir yazı',
            'datePublished' => '2026-09-01T10:00:00+02:00',
            'dateModified' => '2026-09-05T12:00:00+02:00',
            'author' => ['@id' => $data['Person']['@id']],
        ])
            ->and($data['Person']['sameAs'])->toContain('https://github.com/kadirgulec')
            ->and(array_column($data['BreadcrumbList']['itemListElement'], 'name'))->toBe(['Ana Sayfa', 'Yazılar', 'Bir yazı']);
    });

    it('turns a rated film into a review out of 10, and an unrated one into none', function () {
        $film = Watchable::factory()->create(['title' => 'Arrival', 'rating' => 9.5]);
        $series = Watchable::factory()->series()->create();

        $review = structuredData($this->get(url($film->publicPath())))['Review'];

        expect($review['itemReviewed'])->toMatchArray(['@type' => 'Movie', 'name' => 'Arrival'])
            ->and($review['reviewRating'])->toMatchArray(['ratingValue' => 9.5, 'bestRating' => 10])
            ->and(structuredData($this->get(url($series->publicPath()))))->not->toHaveKey('Review');
    });

    it('turns a rated book into a review of the book, with its author and preview image', function () {
        $book = Book::factory()->create(['title' => 'Körlük', 'author' => 'José Saramago', 'translator' => 'Aykut Derman', 'isbn' => '9789752896', 'rating' => 8.5]);

        $response = $this->get(route('books.show', $book->slug));
        $review = structuredData($response)['Review'];

        expect($review['itemReviewed'])->toMatchArray([
            '@type' => 'Book', 'name' => 'Körlük', 'isbn' => '9789752896',
            'author' => [['@type' => 'Person', 'name' => 'José Saramago']],
            'translator' => ['@type' => 'Person', 'name' => 'Aykut Derman'],
        ])
            ->and($review['reviewRating'])->toMatchArray(['ratingValue' => 8.5, 'bestRating' => 10]);

        $response->assertSee(route('og', ['kind' => 'kitap', 'key' => $book->slug, 'v' => $book->updated_at->getTimestamp()]), false);
    });

    it('names the site after Kadir, with the domain as its other name', function () {
        $response = $this->get(route('home'))
            ->assertSee('<meta property="og:site_name" content="Kadir Gülec" />', false)
            ->assertDontSee('twitter:image:alt', false);

        expect(structuredData($response)['WebSite'])->toMatchArray(['name' => 'Kadir Gülec', 'alternateName' => 'kadir.gulec.tr']);
    });

    it('trims the description of a blog post', function () {
        $post = Post::factory()->create(['meta_description' => ' Hetzner üzerine. ']);

        expect(structuredData($this->get(route('posts.show', $post->slug)))['BlogPosting']['description'])->toBe('Hetzner üzerine.');
    });

    it('makes the about page the author profile', function () {
        expect(structuredData($this->get(route('about')))['ProfilePage']['mainEntity'])->toHaveKey('@id');
    });

    it('keeps noindex pages out of search results without describing them', function () {
        $chain = Goal::factory()->chain()->create();

        foreach ([route('goals.chain', $chain->slug), route('privacy'), route('imprint')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<meta name="robots" content="noindex, nofollow" />', false)
                ->assertDontSee('application/ld+json', false);
        }
    });
});

describe('HSTS', function () {
    it('asks browsers to stay on HTTPS in production', function () {
        app()->instance('env', 'production');

        $this->get('https://localhost/hakkimda')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    });

    it('leaves local development alone', function () {
        $this->get(route('about'))->assertHeaderMissing('Strict-Transport-Security');
    });
});
