<?php

use App\Enums\SeriesStatus;
use App\Models\Note;
use App\Models\User;
use App\Models\Watchable;

describe('index', function () {
    it('groups the diary by month, newest first', function () {
        Watchable::factory()->hasViewings(1, ['watched_on' => '2026-09-30'])->create(['title' => 'Kuru Otlar Üstüne']);
        Watchable::factory()->hasViewings(1, ['watched_on' => '2026-08-29'])->create(['title' => 'Bir Düşüşün Anatomisi']);

        $this->get(route('watched.index'))
            ->assertSeeTextInOrder(['Günlük', 'Eylül 2026', 'Kuru Otlar Üstüne', 'Ağustos 2026', 'Bir Düşüşün Anatomisi']);
    });

    it('puts series in progress on the shelf', function () {
        Watchable::factory()->series(SeriesStatus::Paused)->hasViewings(1)->create(['title' => 'Shōgun']);
        Watchable::factory()->series(SeriesStatus::Dropped)->hasViewings(1)->create(['title' => 'Lost']);

        $this->get(route('watched.index'))
            ->assertSeeTextInOrder(['Şu an izliyorum', 'Shōgun', 'Ara verdim', 'Günlük'])
            ->assertSeeText('Bıraktım');
    });

    it('keeps series in progress out of the recent posters', function () {
        Watchable::factory()->hasViewings(1, ['watched_on' => '2026-09-01'])->create(['title' => 'Perfect Days']);
        Watchable::factory()->series(SeriesStatus::Finished)->hasViewings(1, ['watched_on' => '2026-09-10'])->create(['title' => 'The 100']);
        Watchable::factory()->series(SeriesStatus::Dropped)->hasViewings(1, ['watched_on' => '2026-09-20'])->create(['title' => 'Lost']);
        Watchable::factory()->series(SeriesStatus::Watching)->hasViewings(1, ['watched_on' => '2026-10-01'])->create(['title' => 'Shōgun']);
        Watchable::factory()->series(SeriesStatus::Paused)->hasViewings(1, ['watched_on' => '2026-10-02'])->create(['title' => 'Severance']);

        $this->get(route('watched.index'))
            ->assertViewHas('recent', fn (array $recent): bool => array_column($recent, 'title') === ['Lost', 'The 100', 'Perfect Days'])
            ->assertViewHas('currentlyWatching', fn (array $shelf): bool => array_column($shelf, 'title') === ['Severance', 'Shōgun']);
    });

    it('marks a later viewing as a rewatch', function () {
        $film = Watchable::factory()->create(['title' => 'Perfect Days']);
        $film->viewings()->create(['watched_on' => '2024-01-01']);
        $film->viewings()->create(['watched_on' => '2026-05-01']);

        $this->get(route('watched.index'))->assertSeeText('tekrar');
    });

    it('leaves drafts out', function () {
        Watchable::factory()->draft()->hasViewings(1)->create(['title' => 'Taslak Film']);

        $this->get(route('watched.index'))->assertDontSeeText('Taslak Film');
    });
});

describe('show', function () {
    it('renders the review with its spoiler and quote', function () {
        $film = Watchable::factory()->reviewed("Güzel bir film.\n\n:::spoiler\nSonunda ağlıyor.\n:::\n\n:::replik Hirayama\nŞimdi şimdidir.\n:::")
            ->create(['slug' => 'perfect-days']);

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSeeText('Yorumum')
            ->assertSee('data-spoiler', false)
            ->assertSeeText('Şimdi şimdidir.')
            ->assertSeeText('— Hirayama');
    });

    it('shows the collected lines apart from the review, without the hidden ones', function () {
        $film = Watchable::factory()->create();
        $film->quotes()->create(['body' => 'Şimdi şimdidir.', 'said_by' => 'Hirayama', 'published_at' => now()]);
        $film->quotes()->create(['body' => 'Gizli replik.']);

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSeeTextInOrder(['Aklımda kalan replikler', 'Şimdi şimdidir.', '— Hirayama'])
            ->assertDontSeeText('Gizli replik.');
    });

    it('collects the notes that came out of a series', function () {
        $series = Watchable::factory()->series()->create();
        Note::factory()->create(['body' => 'Diziden bir not.', 'source_type' => 'watchable', 'source_id' => $series->id]);

        $this->get(route('watched.show', ['type' => 'dizi', 'slug' => $series->slug]))
            ->assertSeeTextInOrder(['Bu diziden notlarım', 'Diziden bir not.']);
    });

    it('keeps a review with a future publication time hidden', function () {
        $film = Watchable::factory()->create(['review' => 'Gizli yorum.', 'review_published_at' => now()->addDay(), 'overview' => 'TMDB özeti.']);

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertDontSeeText('Gizli yorum.')
            ->assertDontSeeText('Yorumum')
            ->assertSeeText('TMDB özeti.');
    });

    it('shows an unpublished review to the admin only, labelled as a draft', function () {
        $film = Watchable::factory()->create(['review' => 'Taslak yorum.']);
        $url = route('watched.show', ['type' => 'film', 'slug' => $film->slug]);

        $this->get($url)->assertDontSeeText('Taslak yorum.');
        $this->actingAs(User::factory()->admin()->create())->get($url)
            ->assertSeeTextInOrder(['Yorumum', 'Taslak yorum · sadece sen görüyorsun', 'Taslak yorum.']);
    });

    it('does not label a published review as a draft for the admin', function () {
        $film = Watchable::factory()->reviewed()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSeeText('Yorumum')
            ->assertDontSeeText('Taslak yorum · sadece sen görüyorsun');
    });

    it('never puts a spoiler into the diary excerpt', function () {
        Watchable::factory()->reviewed(":::spoiler\nKatil uşak.\n:::\n\nAtmosferi harika.")->hasViewings(1)->create();

        $this->get(route('watched.index'))->assertSeeText('Atmosferi harika.')->assertDontSeeText('Katil uşak.');
    });

    it('falls back to the overview when there is no review', function () {
        $film = Watchable::factory()->create(['overview' => 'Çölde bir savaş.']);

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSeeText('Özet')
            ->assertSeeText('Çölde bir savaş.')
            ->assertSeeText('Bu film hakkında henüz bir şey yazmadım.')
            ->assertDontSeeText('Yorumum');
    });

    it('keeps the overview above the review', function () {
        $film = Watchable::factory()->reviewed('Kızlarım sevmedi.')->create(['overview' => 'Ormanda bir okul.']);

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSeeTextInOrder(['Özet', 'Ormanda bir okul.', 'Yorumum', 'Kızlarım sevmedi.'])
            ->assertDontSeeText('Bu film hakkında henüz bir şey yazmadım.');
    });

    it('leaves out an empty overview section next to a review', function () {
        $film = Watchable::factory()->reviewed()->create(['overview' => null]);

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSeeText('Yorumum')
            ->assertDontSee('id="ozet"', false);
    });

    it('lists the seasons of a series with their progress, marks and notes', function () {
        $series = Watchable::factory()->series(SeriesStatus::Dropped)->create(['current_season' => 3, 'current_episode' => 7]);
        $series->seasons()->createMany([
            ['number' => 1, 'episode_count' => 24, 'rating' => 9.0],
            ['number' => 2, 'episode_count' => 23],
            ['number' => 3, 'episode_count' => 23, 'note' => 'cevap yerine yeni soru'],
        ]);

        $this->get(route('watched.show', ['type' => 'dizi', 'slug' => $series->slug]))
            ->assertSeeTextInOrder(['Sezonlar', '1. sezon', 'bitti ✓', '2. sezon', '3. sezon', 'Bıraktım · B7', 'cevap yerine yeni soru']);
    });

    it('shows the TMDB attribution', function () {
        $film = Watchable::factory()->create();

        $this->get(route('watched.show', ['type' => 'film', 'slug' => $film->slug]))
            ->assertSee('/images/tmdb.svg', false)
            ->assertSeeText('Bu site TMDB tarafından onaylanmış veya desteklenmemektedir.');
    });

    it('returns 404 when the slug belongs to the other type', function () {
        $film = Watchable::factory()->create();

        $this->get('/izlediklerim/dizi/'.$film->slug)->assertNotFound();
    });

    it('returns 404 for an unknown slug or type segment', function () {
        $this->get('/izlediklerim/film/olmayan-film')->assertNotFound();
        $this->get('/izlediklerim/kitap/olmayan-film')->assertNotFound();
    });

    it('shows a draft and its unpublished review to the admin only', function () {
        $film = Watchable::factory()->draft()->create(['review' => 'Taslak yorum.']);
        $url = route('watched.show', ['type' => 'film', 'slug' => $film->slug]);

        $this->get($url)->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get($url)
            ->assertOk()
            ->assertSeeText('Taslak · sadece sen görüyorsun')
            ->assertSeeText('Taslak yorum.');
    });
});
