<?php

use App\Enums\ReviewItemOutcome;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Post;
use App\Models\ReviewItem;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 12:00'));
});

/**
 * October's numbers with one chain and one numeric goal.
 *
 * @return array<string, mixed>
 */
function octoberNumbers(Goal $chain, ?Goal $yearly = null): array
{
    return [
        'chains' => [[
            'goal_id' => $chain->id, 'period' => 'day', 'target' => 1, 'doneDays' => 27, 'excusedDays' => 1,
            'links' => 31, 'held' => 27, 'excused' => 1, 'successRate' => 90, 'bestStreak' => 14, 'record' => null,
        ]],
        'yearly' => $yearly === null ? [] : [[
            'goal_id' => $yearly->id, 'unit' => 'kitap', 'target' => 12, 'added' => 2, 'current' => 10, 'pace' => 'ahead',
        ]],
        'published' => ['posts' => 2, 'notes' => 5, 'viewings' => 3],
        'visitors' => ['views' => 1234, 'visits' => 456, 'topPost' => null],
    ];
}

describe('index', function () {
    it('sends visitors to the latest published month', function () {
        MonthlyReview::factory()->forMonth('2026-09')->create();
        MonthlyReview::factory()->forMonth('2026-10')->create();
        MonthlyReview::factory()->forMonth('2026-11')->draft()->create();

        $this->get(route('goals.reviews.index'))->assertRedirect(route('goals.reviews.show', '2026-10'));
    });

    it('explains the page before the first review is out', function () {
        MonthlyReview::factory()->forMonth('2026-10')->draft()->create();

        $this->get(route('goals.reviews.index'))
            ->assertOk()
            ->assertSee('İlk değerlendirme ayın sonunda burada olacak.');
    });
});

describe('show', function () {
    it('shows the month, its score and the three columns', function () {
        $review = MonthlyReview::factory()->forMonth('2026-10')->create(['summary' => 'Spor oturdu, yazı aksadı.', 'score' => 8]);
        ReviewItem::factory()->for($review, 'review')->create(['body' => 'Her hafta üç gün spor']);
        ReviewItem::factory()->for($review, 'review')->hard()->create(['body' => 'Sadece **iki** yazı']);
        ReviewItem::factory()->for($review, 'review')->toTry()->create(['body' => 'Pazar sabahları yazı']);

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            ->assertSeeInOrder(['Ekim 2026', 'Spor oturdu, yazı aksadı.', '8', 'ayın puanı'])
            ->assertSeeInOrder(['İyi giden', 'Her hafta üç gün spor', 'Zorlandığım', 'Sadece <strong>iki</strong> yazı', "Kasım'da deneyeceğim", 'Pazar sabahları yazı'], escape: false);
    });

    it('shows the frozen numbers with the goals\' current titles', function () {
        $chain = Goal::factory()->chain('')->create(['title' => 'Kitap okuma']);
        $yearly = Goal::factory()->yearly()->create(['title' => '12 kitap']);
        MonthlyReview::factory()->forMonth('2026-10')->create(['stats' => octoberNumbers($chain, $yearly)]);
        $chain->update(['title' => 'Her gün kitap']);

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            // Each label comes before its number in the HTML; CSS puts the number on top.
            ->assertSeeInOrder(['gün', 'Her gün kitap', 'başarı %90', '27'])
            ->assertSeeInOrder(['kitap', '12 kitap', '10 / 12 kitap · önde', '+2'])
            ->assertSeeInOrder(['sayfa görüntüleme', '1.234', 'ziyaret', '456']);
    });

    it('blacks out a goal censored since, unless the viewer may read it', function () {
        $chain = Goal::factory()->chain('')->censored()->create(['title' => 'Gizli alışkanlık']);
        MonthlyReview::factory()->forMonth('2026-10')->create(['stats' => octoberNumbers($chain)]);

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            ->assertSee('27')
            ->assertSee('sansürlü hedef')
            ->assertDontSee('Gizli alışkanlık');

        $this->actingAs(User::factory()->close()->create())
            ->get(route('goals.reviews.show', '2026-10'))
            ->assertSee('Gizli alışkanlık');
    });

    it('drops the tile of a goal hidden since and the tiles Kadir hid', function () {
        $chain = Goal::factory()->chain('')->hidden()->create(['title' => 'Artık gizli']);
        MonthlyReview::factory()->forMonth('2026-10')->create(['stats' => octoberNumbers($chain), 'hidden_stats' => ['views', 'visits']]);

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            ->assertDontSee('Artık gizli')
            ->assertDontSee('sayfa görüntüleme')
            ->assertSee('film / dizi');
    });

    it('links the most read post that is still published', function () {
        $post = Post::factory()->create(['title' => 'Okunan yazı', 'slug' => 'okunan-yazi']);
        $numbers = octoberNumbers(Goal::factory()->chain('')->create());
        $numbers['visitors']['topPost'] = ['post_id' => $post->id, 'views' => 321];
        MonthlyReview::factory()->forMonth('2026-10')->create(['stats' => $numbers]);

        $this->get(route('goals.reviews.show', '2026-10'))->assertSee(route('posts.show', 'okunan-yazi'))->assertSee('321 görüntüleme');

        $post->update(['published_at' => null]);

        $this->get(route('goals.reviews.show', '2026-10'))->assertDontSee('Okunan yazı');
    });

    it('looks back on last month\'s "try" items with how they went', function () {
        $september = MonthlyReview::factory()->forMonth('2026-09')->create();
        ReviewItem::factory()->for($september, 'review')->toTry()->create(['body' => 'Sabah Almancası', 'outcome' => ReviewItemOutcome::Done]);
        ReviewItem::factory()->for($september, 'review')->toTry()->create(['body' => 'Ayda iki yazı', 'outcome' => ReviewItemOutcome::NotDone]);
        MonthlyReview::factory()->forMonth('2026-10')->create();

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            ->assertSeeInOrder(["Ekim'de denediklerim", 'Sabah Almancası', 'Ayda iki yazı', 'olmadı']);
    });

    it('keeps the "try" items of an unpublished month to itself', function () {
        $september = MonthlyReview::factory()->forMonth('2026-09')->draft()->create();
        ReviewItem::factory()->for($september, 'review')->toTry()->create(['body' => 'Taslaktaki deneme']);
        MonthlyReview::factory()->forMonth('2026-10')->create();

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            ->assertDontSee('Taslaktaki deneme');
    });

    it('shows a draft only to those who manage goals', function () {
        MonthlyReview::factory()->forMonth('2026-10')->draft()->create();

        $this->get(route('goals.reviews.show', '2026-10'))->assertNotFound();
        $this->actingAs(User::factory()->member()->create())->get(route('goals.reviews.show', '2026-10'))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('goals.reviews.show', '2026-10'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow" />', escape: false);
    });

    it('returns 404 for a month without a review', function (string $month) {
        MonthlyReview::factory()->forMonth('2026-10')->create();

        $this->get('/hedefler/aylik/'.$month)->assertNotFound();
    })->with(['missing month' => '2026-08', 'month 13' => '2026-13', 'month 00' => '2026-00']);

    it('links the months around it', function () {
        MonthlyReview::factory()->forMonth('2026-08')->create();
        MonthlyReview::factory()->forMonth('2026-09')->draft()->create();
        MonthlyReview::factory()->forMonth('2026-10')->create();

        $this->get(route('goals.reviews.show', '2026-10'))
            ->assertSee('← Ağustos 2026')
            ->assertDontSee('Eylül 2026');
    });
});

it('puts the latest review on the goals page', function () {
    MonthlyReview::factory()->forMonth('2026-10')->create(['summary' => 'Dürüst bir ay.', 'score' => 7]);
    MonthlyReview::factory()->forMonth('2026-11')->draft()->create(['summary' => 'Henüz taslak.']);

    $this->get(route('goals.index'))
        ->assertOk()
        ->assertSee(route('goals.reviews.show', '2026-10'))
        ->assertSee('Dürüst bir ay.')
        ->assertDontSee('Henüz taslak.');
});
