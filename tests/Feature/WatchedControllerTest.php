<?php

describe('index', function () {
    it('groups the diary by month, newest first', function () {
        $response = $this->get(route('watched.index'));

        $response->assertSeeTextInOrder(['Günlük', 'Eylül 2026', 'Kuru Otlar Üstüne', 'Ağustos 2026', 'Bir Düşüşün Anatomisi']);
    });

    it('puts paused series on the shelf and dropped series in the diary', function () {
        $response = $this->get(route('watched.index'));

        $response->assertSeeTextInOrder(['Şu an izliyorum', 'Shōgun', 'Ara verdim', 'Günlük', 'Lost', 'Bıraktım']);
    });
});

describe('show', function () {
    it('renders the review with its spoiler and quote', function () {
        $response = $this->get(route('watched.show', ['type' => 'film', 'slug' => 'perfect-days']));

        $response->assertSeeText('Yorumum')
            ->assertSee('data-spoiler', false)
            ->assertSeeText('Bir dahaki sefer bir dahaki seferdir. Şimdi şimdidir.');
    });

    it('falls back to the overview when there is no review', function () {
        $response = $this->get(route('watched.show', ['type' => 'film', 'slug' => 'dune-part-two']));

        $response->assertSeeText('Özet')
            ->assertSeeText('Bu film hakkında henüz bir şey yazmadım.')
            ->assertDontSeeText('Yorumum');
    });

    it('returns 404 when the slug belongs to the other type', function () {
        $response = $this->get('/izlediklerim/dizi/perfect-days');

        $response->assertNotFound();
    });

    it('returns 404 for an unknown slug', function () {
        $response = $this->get('/izlediklerim/film/olmayan-film');

        $response->assertNotFound();
    });

    it('returns 404 for an unknown type segment', function () {
        $response = $this->get('/izlediklerim/kitap/perfect-days');

        $response->assertNotFound();
    });
});
