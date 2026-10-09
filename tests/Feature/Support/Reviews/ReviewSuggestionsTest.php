<?php

use App\Enums\GoalMeasure;
use App\Enums\ReviewItemKind;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Post;
use App\Support\Reviews\ReviewSuggestions;

/**
 * The suggestions for a review with these numbers (the rest empty, one post published).
 *
 * @param  array<string, mixed>  $stats
 * @return list<array{kind: ReviewItemKind, text: string}>
 */
function suggestionsFor(array $stats): array
{
    $review = MonthlyReview::factory()->make(['stats' => [
        'chains' => [],
        'yearly' => [],
        'published' => ['posts' => 1, 'notes' => 0, 'viewings' => 0],
        'visitors' => ['views' => 0, 'visits' => 0, 'topPost' => null],
        ...$stats,
    ]]);

    return app(ReviewSuggestions::class)->for($review);
}

/**
 * @return array<string, mixed>
 */
function chainNumbers(Goal $chain, int $held, int $links, ?int $record = null, string $period = 'day'): array
{
    return [
        'goal_id' => $chain->id, 'period' => $period, 'target' => 1, 'doneDays' => $held, 'excusedDays' => 0,
        'links' => $links, 'held' => $held, 'excused' => 0, 'successRate' => (int) round($held / $links * 100),
        'bestStreak' => $held, 'record' => $record,
    ];
}

it('suggests a chain by how often it held', function (int $held, ?array $expected) {
    $chain = Goal::factory()->chain('')->create(['title' => 'Kitap']);

    $suggestions = suggestionsFor(['chains' => [chainNumbers($chain, $held, 30)]]);

    expect($suggestions)->toBe($expected === null ? [] : [['kind' => $expected[0], 'text' => $expected[1]]]);
})->with([
    'held well' => [28, [ReviewItemKind::Good, 'Kitap: 28 / 30 gün tuttu']],
    'held poorly' => [9, [ReviewItemKind::Hard, 'Kitap: sadece 9 / 30 gün tuttu']],
    'in between' => [20, null],
]);

it('suggests a new record in the chain\'s own unit', function () {
    $chain = Goal::factory()->chain('')->create(['title' => 'Spor']);

    expect(suggestionsFor(['chains' => [chainNumbers($chain, 3, 4, record: 6, period: 'week')]])[0])
        ->toBe(['kind' => ReviewItemKind::Good, 'text' => 'Spor: yeni rekor seri, 6 hafta']);
});

it('suggests numeric goals that are ahead or behind', function () {
    $ahead = Goal::factory()->yearly(GoalMeasure::Numeric)->create(['title' => '20 kitap']);
    $behind = Goal::factory()->yearly(GoalMeasure::Numeric)->create(['title' => '24 yazı']);
    $onTrack = Goal::factory()->yearly(GoalMeasure::Numeric)->create(['title' => '12 film']);

    $suggestions = suggestionsFor(['yearly' => [
        ['goal_id' => $ahead->id, 'unit' => 'kitap', 'target' => 20, 'added' => 3, 'current' => 19, 'pace' => 'ahead'],
        ['goal_id' => $behind->id, 'unit' => null, 'target' => 24, 'added' => 0, 'current' => 4, 'pace' => 'behind'],
        ['goal_id' => $onTrack->id, 'unit' => 'film', 'target' => 12, 'added' => 1, 'current' => 10, 'pace' => 'on-track'],
    ]]);

    expect($suggestions)->toBe([
        ['kind' => ReviewItemKind::Good, 'text' => '20 kitap: önde (19 / 20 kitap)'],
        ['kind' => ReviewItemKind::Hard, 'text' => '24 yazı: biraz geride (4 / 24)'],
    ]);
});

it('suggests the month\'s writing by how many posts came out', function (int $posts, array $expected) {
    $suggestions = suggestionsFor(['published' => ['posts' => $posts, 'notes' => 0, 'viewings' => 0]]);

    expect(array_column($suggestions, 'text'))->toBe($expected);
})->with([
    'none' => [0, ['Bu ay hiç yazı yayınlanmadı']],
    'one' => [1, []],
    'several' => [3, ['3 yazı yayınlandı']],
]);

it('suggests the most read post by its current title', function () {
    $post = Post::factory()->create(['title' => 'Eski başlık']);
    $post->update(['title' => 'Yeni başlık']);

    $suggestions = suggestionsFor(['visitors' => ['views' => 10, 'visits' => 5, 'topPost' => ['post_id' => $post->id, 'views' => 6]]]);

    expect($suggestions)->toBe([['kind' => ReviewItemKind::Good, 'text' => '"Yeni başlık" ayın en çok okunan yazısı oldu']]);
});

it('skips goals that were deleted since', function () {
    $chain = Goal::factory()->chain('')->create();
    $numbers = chainNumbers($chain, 30, 30);
    $chain->delete();

    expect(suggestionsFor(['chains' => [$numbers]]))->toBe([]);
});
