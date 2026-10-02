<?php

describe('index', function () {
    it('shows featured posts above the table of contents', function () {
        $response = $this->get(route('posts.index'));

        $response->assertSeeTextInOrder(['Öne çıkanlar', 'Yapay zekâyla kod yazarken', 'Fihrist', '2026', 'Alpine.js ile 40 satırda', '2025', "Almanya'da bir Türk yazılımcı"]);
    });

    it('filters the list by tag', function () {
        $response = $this->get(route('posts.index', ['etiket' => 'kariyer']));

        $response->assertSeeText('#kariyer etiketli yazılar')
            ->assertSeeText('Yeniden çırak olmak')
            ->assertDontSeeText('Alpine.js ile 40 satırda')
            ->assertDontSeeText('Öne çıkanlar');
    });

    it('returns 404 for an unknown tag', function () {
        $response = $this->get(route('posts.index', ['etiket' => 'olmayan-etiket']));

        $response->assertNotFound();
    });
});

describe('show', function () {
    it('renders the body with highlights, sidenotes and code', function () {
        $response = $this->get(route('posts.show', 'yapay-zekayla-kod-yazarken-bes-kural'));

        $response->assertSee('<mark class="marker">', false)
            ->assertSee('class="sidenote"', false)
            ->assertSee('data-code-block', false)
            ->assertSeeText('Bu kurallar küçük bir ekipte');
    });

    it('links to the older post and back from it to the newer one', function () {
        $newest = $this->get(route('posts.show', 'yapay-zekayla-kod-yazarken-bes-kural'));
        $older = $this->get(route('posts.show', 'livewire-tek-dosyali-bilesenler'));

        $newest->assertSeeTextInOrder(['daha eski', "Livewire'da tek dosyalı"])->assertDontSeeText('daha yeni');
        $older->assertSeeTextInOrder(['daha eski', 'Alpine.js ile 40 satırda', 'daha yeni', 'Yapay zekâyla kod yazarken']);
    });

    it('returns 404 for an unknown post', function () {
        $response = $this->get(route('posts.show', 'olmayan-yazi'));

        $response->assertNotFound();
    });
});
