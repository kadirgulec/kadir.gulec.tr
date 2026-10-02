<?php

use Carbon\CarbonImmutable;

it('shows the three floors from daily chains to the long-term board', function () {
    $response = $this->get(route('goals.index'));

    $response->assertSeeTextInOrder(['Zincirler', 'Her gün 30 dk kod', 'hedefleri', "CoMon'u herkese açık yayınla", 'Geçmiş yıllar', 'Uzun vade', 'Kendi ürünümü çıkarmak']);
});

it('never sends the words of censored goals to the browser', function () {
    $response = $this->get(route('goals.index'));

    $response->assertDontSee('Ekransız sabahlar')
        ->assertDontSee('Kimseye söylemediğim bir hedef')
        ->assertDontSee('Çok kişisel bir hedef')
        ->assertDontSee('sadece ben biliyorum')
        ->assertSeeText('🔥 41');
});

it('leaves hidden goals out completely', function () {
    $response = $this->get(route('goals.index'));

    $response->assertDontSee('Tamamen gizli bir alışkanlık');
});

it('links a goal to the bigger goal it serves', function () {
    $response = $this->get(route('goals.index'));

    $response->assertSee('href="#hedef-kendi-urunum"', false)
        ->assertSee('id="hedef-kendi-urunum"', false);
});

it('tells whether a numeric goal is on track', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-02'));

    $response = $this->get(route('goals.index'));

    $response->assertSeeTextInOrder(['12 kitap oku', 'önde', '500 km koş', 'yolunda', '24 blog yazısı yayınla', 'biraz geride']);
});

it('keeps censored chains censored on the home page too', function () {
    $response = $this->get(route('home'));

    $response->assertDontSee('Ekransız sabahlar')
        ->assertSeeText('sansürlü zincir');
});

describe('chain page', function () {
    it('shows the yearly numbers of a chain', function () {
        $response = $this->get(route('goals.chain', 'her-gun-kod'));

        $response->assertSeeTextInOrder(['Her gün 30 dk kod', 'şu anki seri', '🔥 23', 'en uzun seri', 'başarı', 'boyunca']);
    });

    it('keeps the title of a censored chain out of the page', function () {
        $response = $this->get(route('goals.chain', 'gizli-zincir-1'));

        $response->assertOk()
            ->assertDontSee('Ekransız sabahlar')
            ->assertSeeText('sansürlü zincir');
    });

    it('returns 404 for a hidden chain', function () {
        $response = $this->get(route('goals.chain', 'gizli-zincir-2'));

        $response->assertNotFound();
    });
});

describe('long-term goal page', function () {
    it('shows the yearly goals and chains that serve it, and its story', function () {
        $response = $this->get(route('goals.show', 'almanca'));

        $response->assertSeeTextInOrder(['Almancayı Türkçe kadar rahat konuşmak', 'Bu hedefe hizmet edenler', 'Almanca C1 sınavını geç', 'Her gün 10 sayfa Almanca', 'Hikâyesi', 'İlk kez bir müşteri toplantısını']);
    });

    it('returns 404 for a censored long-term goal', function () {
        $response = $this->get(route('goals.show', 'gizli-uzun-1'));

        $response->assertNotFound();
    });
});
