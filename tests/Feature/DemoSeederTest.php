<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\Watchable;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

it('fills the watchlist with unwatched drafts in file order, and runs again without duplicates', function () {
    Storage::fake('public');

    $this->seed(DemoSeeder::class);
    $count = Watchable::query()->count();
    $watchlist = Watchable::query()->onWatchlist()->withCount('viewings')->get();

    expect($watchlist->pluck('title')->all())->toBe(['Mad Men', 'Dark', 'Blade Runner 2049', 'Ahlat Ağacı', 'Better Call Saul'])
        ->and($watchlist->first()->watchlist_note)->toBe('Üç bölüm izledim, devam edip etmeyeceğime karar veremedim.')
        ->and($watchlist->every(fn (Watchable $watchable): bool => $watchable->published_at === null && $watchable->viewings_count === 0))->toBeTrue();

    $this->seed(DemoSeeder::class);

    expect(Watchable::query()->count())->toBe($count)
        ->and(Watchable::query()->onWatchlist()->count())->toBe(5);
});

it('seeds the sample notes with their tags once, keeping drafts as drafts', function () {
    Storage::fake('public');

    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Note::query()->count())->toBe(13)
        ->and(Note::query()->published()->count())->toBe(12)
        ->and(Tag::query()->where('name', 'laravel')->sole()->notes()->count())->toBe(2);
});
