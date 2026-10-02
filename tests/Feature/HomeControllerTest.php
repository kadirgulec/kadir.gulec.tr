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

it('shows one snippet from every section on the lately board', function () {
    $response = $this->get(route('home'));

    $response->assertSeeTextInOrder([
        'Kuru Otlar Üstüne',
        'Yapay zekâyla kod yazarken kendime koyduğum beş kural',
        'Her gün 30 dk kod',
        'Severance',
        'CoMon',
    ]);
});

it('tells when the last film was watched in Turkish', function () {
    $response = $this->get(route('home'));

    $response->assertSeeText("30 Eylül'de izledim");
});
