<?php

use App\Models\Post;
use App\Enums\SeriesStatus;
use App\Models\Project;
use App\Models\Watchable;

beforeEach(function () {
    Project::factory()->featured()->create(['name' => 'CoMon']);
    Post::factory()->create(['title' => 'Yapay zekâyla kod yazarken kendime koyduğum beş kural']);
    Watchable::factory()->hasViewings(1, ['watched_on' => '2026-09-30'])->create(['title' => 'Kuru Otlar Üstüne']);
    Watchable::factory()->series(SeriesStatus::Watching)->hasViewings(1, ['watched_on' => '2026-09-20'])->create(['title' => 'Severance']);
});

it('tells the story from Ankara to Düren in order', function () {
    $response = $this->get(route('about'));

    $response->assertSeeTextInOrder(['Hikâyem', 'Lise', 'İçişleri Bakanlığı, memur', 'Ankara → Düren', 'Yeniden çırak: Fachinformatiker', 'Yazılım geliştirici, aks-Service GmbH']);
});

it('fills the "now" list from the other sections', function () {
    $response = $this->get(route('about'));

    $response->assertSeeTextInOrder(['Şu an', 'CoMon', 'Severance', 'Her gün 30 dk kod', '23. gündeyim', 'Yapay zekâyla kod yazarken']);
});

it('never puts a censored chain on the "now" list', function () {
    $response = $this->get(route('about'));

    $response->assertDontSee('Ekransız sabahlar');
});
