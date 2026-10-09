<?php

use App\Mail\MonthlyReviewReady;
use App\Models\Goal;
use App\Models\MonthlyReview;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config(['mail.contact_to' => 'kadir@example.com']);
    $this->travelTo(CarbonImmutable::parse('2026-11-01 00:15'));
});

it('makes last month\'s draft with its numbers frozen and tells Kadir', function () {
    $chain = Goal::factory()->chain('')->create(['started_on' => '2026-10-01']);
    $chain->chainDays()->create(['date' => '2026-10-10', 'state' => 'done']);

    $this->artisan('reviews:create')->assertSuccessful();

    $review = MonthlyReview::query()->sole();
    expect($review->month->toDateString())->toBe('2026-10-01')
        ->and($review->published_at)->toBeNull()
        ->and($review->stats['chains'][0])->toMatchArray(['goal_id' => $chain->id, 'doneDays' => 1]);

    Mail::assertSent(MonthlyReviewReady::class, fn (MonthlyReviewReady $mail): bool => $mail->hasTo('kadir@example.com')
        && $mail->envelope()->subject === 'Ekim değerlendirmesi hazır');
});

it('leaves an existing review alone and sends nothing', function () {
    $review = MonthlyReview::factory()->forMonth('2026-10')->create(['summary' => 'Yazıldı bile']);

    $this->artisan('reviews:create')->assertSuccessful();

    expect(MonthlyReview::query()->sole()->summary)->toBe('Yazıldı bile');
    Mail::assertNothingSent();
});

it('makes the review of the month it is given', function () {
    $this->artisan('reviews:create', ['month' => '2026-09'])->assertSuccessful();

    expect(MonthlyReview::query()->sole()->month->toDateString())->toBe('2026-09-01');
});

it('rejects a month in another shape', function (string $month) {
    $this->artisan('reviews:create', ['month' => $month])->assertFailed();

    expect(MonthlyReview::query()->count())->toBe(0);
})->with(['day included' => '2026-10-01', 'month 13' => '2026-13', 'words' => 'ekim']);

it('still makes the draft when there is no address for Kadir', function () {
    config(['mail.contact_to' => null, 'legal.email' => null]);

    $this->artisan('reviews:create')->assertSuccessful();

    expect(MonthlyReview::query()->count())->toBe(1);
    Mail::assertNothingSent();
});

it('lists the suggestions in the e-mail and links the review\'s edit page', function () {
    $review = MonthlyReview::factory()->forMonth('2026-10')->draft()->create();

    $html = (new MonthlyReviewReady($review))->render();

    expect($html)->toContain('Ekim 2026 değerlendirmesinin taslağı hazır')
        ->toContain('Bu ay hiç yazı yayınlanmadı')
        ->toContain(route('admin.reviews.edit', $review));
});
