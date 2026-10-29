<?php

use App\Actions\Goals\SetGoalsSectionOpen;
use App\Enums\ChainPeriod;
use App\Enums\Section;
use App\Mail\ChainReminder;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function closeGoalsSection(): void
{
    app(SetGoalsSectionOpen::class)->handle(false);
}

describe('site', function () {
    it('answers 404 on every goals address to anyone who does not manage goals', function (?string $role) {
        $goal = Goal::factory()->create();
        $chain = Goal::factory()->chain()->create();
        MonthlyReview::factory()->forMonth('2026-09')->create(['published_at' => now()->subDay()]);
        $paths = ['/hedefler', '/hedefler/aylik', '/hedefler/aylik/2026-09', '/hedefler/aylik/rss', $goal->publicPath(), $chain->publicPath()];

        if ($role !== null) {
            $this->actingAs(User::factory()->{$role}()->create());
        }

        foreach ($paths as $path) {
            expect($this->get($path)->status())->not->toBe(404);
        }

        closeGoalsSection();

        foreach ($paths as $path) {
            $this->get($path)->assertNotFound();
        }
    })->with([
        'guest' => [null],
        'member' => ['member'],
        'close friend' => ['close'],
    ]);

    it('still shows Kadir the pages, marked closed and kept from search engines', function () {
        $goal = Goal::factory()->create();
        closeGoalsSection();
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/hedefler')
            ->assertOk()
            ->assertSee('Hedefler kapalı · sadece sen görüyorsun')
            ->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
        $this->get($goal->publicPath())->assertOk();
    });

    it('takes the section off the tabs and the other pages', function (string $page, string $mention) {
        Goal::factory()->chain()->create(['title' => 'Ekransız sabahlar']);
        Goal::factory()->countingBooks()->create(['target' => 12]);
        Project::factory()->create(['slug' => 'defter', 'goal_id' => Goal::factory()->create(['title' => 'Kendi ürünüm'])->id]);

        $this->get($page)->assertSee($mention);

        closeGoalsSection();

        $this->get($page)
            ->assertOk()
            ->assertDontSee($mention)
            ->assertDontSee(url('/hedefler'), false);
    })->with([
        'home: the chains card' => ['/', 'tüm hedefler →'],
        'about: the chain in "now"' => ['/hakkimda', 'hedeflerim →'],
        'books: the reading goal' => ['/okuduklarim', 'hedef 0 / 12'],
        'project: its goal' => ['/projeler/defter', 'Kendi ürünüm'],
    ]);

    it('leaves the section out of the sitemap', function () {
        $goal = Goal::factory()->create();

        $this->get('/sitemap.xml')->assertSee(url($goal->publicPath()));

        closeGoalsSection();

        $this->get('/sitemap.xml')->assertOk()->assertDontSee(url('/hedefler'));
    });
});

describe('notifications', function () {
    it('tells followers nothing about goals while closed, not even after reopening', function () {
        $goal = Goal::factory()->yearly()->create(['target' => 4]);
        $member = User::factory()->member()->create();
        $member->follows()->create(['followable_type' => $goal->getMorphClass(), 'followable_id' => $goal->id]);
        closeGoalsSection();

        $goal->progressEntries()->create(['date' => today(), 'amount' => 4]);
        app(SetGoalsSectionOpen::class)->handle(true);

        expect($member->notificationItems()->count())->toBe(0);
    });

    it('does not announce a monthly review published while closed, not even after reopening', function () {
        $subscriber = User::factory()->member()->create(['notify_monthly_reviews' => true]);
        MonthlyReview::factory()->forMonth('2026-09')->create(['published_at' => now()->subMinute()]);
        closeGoalsSection();

        $this->artisan('notifications:announce');
        app(SetGoalsSectionOpen::class)->handle(true);
        $this->artisan('notifications:announce');

        expect($subscriber->notificationItems()->count())->toBe(0);
    });

    it('drops the goal news followers were still waiting for, and keeps the rest', function () {
        $goal = Goal::factory()->create();
        $post = Post::factory()->create();
        $member = User::factory()->member()->create();
        $waiting = ['title' => 'Bir şey oldu', 'url' => url('/')];
        $member->notificationItems()->create(['key' => 'goal:'.$goal->id.':update-1', ...$waiting])->subject()->associate($goal)->save();
        $member->notificationItems()->create(['key' => 'goal:'.$goal->id.':update-2', ...$waiting, 'sent_at' => now()])->subject()->associate($goal)->save();
        $member->notificationItems()->create(['key' => 'post:'.$post->id.':published', ...$waiting])->subject()->associate($post)->save();

        closeGoalsSection();

        expect($member->notificationItems()->orderBy('id')->pluck('key')->all())->toBe(['goal:'.$goal->id.':update-2', 'post:'.$post->id.':published']);
    });

    it('keeps reminding Kadir of his chains', function () {
        Mail::fake();
        config(['mail.contact_to' => 'kadir@example.test']);
        $this->travelTo(CarbonImmutable::parse('2026-10-08 08:00'));
        Goal::factory()->chain('')->per(ChainPeriod::Week, 2)->create(['started_on' => '2026-09-28']);
        closeGoalsSection();

        $this->artisan('chains:remind')->assertSuccessful();

        Mail::assertSent(ChainReminder::class);
    });
});

it('closes and reopens the section from the goals page in the admin panel', function () {
    $this->actingAs(User::factory()->admin()->create());

    $component = Livewire::test('pages::admin.goals.index')->assertSet('isOpen', true)->set('isOpen', false);

    expect(Section::Goals->isOpen())->toBeFalse();

    $component->set('isOpen', true);

    expect(Section::Goals->isOpen())->toBeTrue();
});
