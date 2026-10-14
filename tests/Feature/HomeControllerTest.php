<?php

use App\Enums\SeriesStatus;
use App\Models\Book;
use App\Models\Goal;
use App\Models\Post;
use App\Models\Project;
use App\Models\Reading;
use App\Models\Watchable;

beforeEach(function () {
    Project::factory()->featured()->create(['name' => 'CoMon']);
    Post::factory()->create(['title' => 'Yapay zekâyla kod yazarken kendime koyduğum beş kural']);
    Watchable::factory()->hasViewings(1, ['watched_on' => '2026-09-30'])->create(['title' => 'Kuru Otlar Üstüne']);
    Goal::factory()->chain(str_repeat('x', 23))->create(['title' => 'Her gün 30 dk kod']);
    Goal::factory()->chain(str_repeat('x', 41))->censored()->create(['title' => 'Ekransız sabahlar']);
    Watchable::factory()->series(SeriesStatus::Watching)->hasViewings(1, ['watched_on' => '2026-09-20'])->create(['title' => 'Severance']);
});

it('shows one snippet from every section on the lately board', function () {
    $response = $this->get(route('home'));

    $response->assertSeeTextInOrder([
        'Kuru Otlar Üstüne',
        'Severance',
        'Her gün 30 dk kod',
        'Yapay zekâyla kod yazarken kendime koyduğum beş kural',
        'CoMon',
    ]);
});

it('puts the books being read under the series being watched', function () {
    Reading::factory()->reading(43)->for(Book::factory()->create(['title' => 'Saatleri Ayarlama Enstitüsü', 'author' => 'Ahmet Hamdi Tanpınar']))->create();

    $this->get(route('home'))
        ->assertSeeTextInOrder(['şu an izliyorum', 'Severance', 'şu an okuyorum', 'Saatleri Ayarlama Enstitüsü', '%43', 'Ahmet Hamdi Tanpınar', 'zincirler']);
});

it('leaves the reading note out when no book is being read', function () {
    $this->get(route('home'))->assertDontSeeText('şu an okuyorum');
});

it('tells when the last film was watched in Turkish', function () {
    $response = $this->get(route('home'));

    $response->assertSeeText("30 Eylül'de izledim");
});

it('shows the last watched title without a mark when it has no rating yet', function () {
    Watchable::factory()->series(SeriesStatus::Finished)->hasViewings(1, ['watched_on' => '2026-10-04'])->create(['title' => 'The 100', 'rating' => null]);

    $this->get(route('home'))->assertOk()->assertSeeText('The 100');
});

it('shows the last finished title, not a series still being watched', function () {
    Watchable::factory()->series(SeriesStatus::Watching)->hasViewings(1, ['watched_on' => '2026-10-05'])->create(['title' => 'Slow Horses']);

    $this->get(route('home'))->assertSeeTextInOrder(['son izlediğim', 'Kuru Otlar Üstüne', 'şu an izliyorum', 'Slow Horses']);
});

it('shows a dropped series as the last watched title', function () {
    Watchable::factory()->series(SeriesStatus::Dropped)->hasViewings(1, ['watched_on' => '2026-10-05'])->create(['title' => 'Lost']);

    $this->get(route('home'))->assertSeeTextInOrder(['son izlediğim', 'Lost', 'şu an izliyorum', 'Severance']);
});
