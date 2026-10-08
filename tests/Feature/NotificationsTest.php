<?php

use App\Enums\GoalMeasure;
use App\Enums\NotificationFrequency;
use App\Enums\ProjectStatus;
use App\Enums\SeriesStatus;
use App\Mail\NotificationDigest;
use App\Models\Comment;
use App\Models\Goal;
use App\Models\Note;
use App\Models\NotificationItem;
use App\Models\Post;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Models\Watchable;
use App\Support\Notifications\Announcements;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function follower(string $role = 'member', array $attributes = []): User
{
    return User::factory()->{$role}()->create($attributes);
}

function follow(User $user, Model $followable): void
{
    $user->follows()->create(['followable_type' => $followable->getMorphClass(), 'followable_id' => $followable->getKey()]);
}

describe('follow button', function () {
    it('follows and unfollows for a member', function () {
        $film = Watchable::factory()->create();
        $member = follower();
        $this->actingAs($member);

        $component = Livewire::test('site.follow-button', ['type' => 'watchable', 'id' => $film->id])->call('toggle');
        expect($film->isFollowedBy($member))->toBeTrue();

        $component->call('toggle');
        expect($film->isFollowedBy($member))->toBeFalse();
    });

    it('asks guests to sign in and refuses unverified members', function () {
        $film = Watchable::factory()->create();

        Livewire::test('site.follow-button', ['type' => 'watchable', 'id' => $film->id])->assertSee('takip etmek için giriş yap');

        $this->actingAs(User::factory()->member()->unverified()->create());
        Livewire::test('site.follow-button', ['type' => 'watchable', 'id' => $film->id])->call('toggle')->assertForbidden();
    });

    it('does not follow hidden goals or unknown kinds', function () {
        $this->actingAs(follower());

        Livewire::test('site.follow-button', ['type' => 'goal', 'id' => Goal::factory()->chain()->hidden()->create()->id])->call('toggle')->assertNotFound();
        Livewire::test('site.follow-button', ['type' => 'user', 'id' => 1])->assertNotFound();
    });
});

describe('moments', function () {
    it('tells followers about a new viewing, once', function () {
        $series = Watchable::factory()->series()->create(['title' => 'Severance']);
        follow($member = follower(), $series);
        $viewing = $series->viewings()->create(['watched_on' => today(), 'note' => "S2'yi bitirdim"]);

        app(Announcements::class)->viewing($viewing);

        expect($member->notificationItems()->sole()->title)->toBe("Severance: S2'yi bitirdim");
    });

    it('skips members who get no e-mail', function (array $state) {
        $film = Watchable::factory()->create();
        follow($member = User::factory()->member()->create($state), $film);

        $film->viewings()->create(['watched_on' => today()]);

        expect($member->notificationItems()->count())->toBe(0);
    })->with([
        'blocked' => [['blocked_at' => now()]],
        'unverified' => [['email_verified_at' => null]],
        'never' => [['notification_frequency' => NotificationFrequency::Never]],
    ]);

    it('tells about a series status, a season note and nothing about drafts', function () {
        $series = Watchable::factory()->series()->create();
        $draft = Watchable::factory()->series()->draft()->create();
        follow($member = follower(), $series);
        follow($member, $draft);

        $series->update(['series_status' => SeriesStatus::Finished]);
        $series->seasons()->create(['number' => 1, 'episode_count' => 9, 'note' => 'harika final']);
        $draft->update(['series_status' => SeriesStatus::Finished]);

        expect($member->notificationItems()->pluck('title')->all())->toHaveCount(2);
    });

    it('announces a scheduled review only once its time came', function () {
        $film = Watchable::factory()->create(['title' => 'Perfect Days', 'review' => 'Güzel.', 'review_published_at' => now()->addHour()]);
        follow($member = follower(), $film);

        $this->artisan('notifications:announce')->assertSuccessful();
        expect($member->notificationItems()->count())->toBe(0);

        $this->travel(2)->hours();
        $this->artisan('notifications:announce');
        $this->artisan('notifications:announce');

        expect($member->notificationItems()->sole()->title)->toBe('Perfect Days hakkında yorum yazdım');
    });

    it('announces new posts to subscribers only', function () {
        $subscriber = follower(attributes: ['notify_new_posts' => true]);
        $other = follower();
        Post::factory()->create(['title' => 'Yeni yazı', 'published_at' => now()->subMinute()]);

        $this->artisan('notifications:announce');

        expect($subscriber->notificationItems()->sole()->title)->toBe('Yeni yazı: Yeni yazı')
            ->and($other->notificationItems()->count())->toBe(0);
    });

    it('announces new notes to subscribers once, for the digest only', function () {
        $subscriber = follower(attributes: ['notify_new_notes' => true]);
        $other = follower(attributes: ['notify_new_posts' => true]);
        Note::factory()->for(Tag::factory()->state(['name' => 'git']))->create(['body' => '`git switch -` önceki dala döner.', 'published_at' => now()->subMinute()]);

        $this->artisan('notifications:announce');
        $this->artisan('notifications:announce');

        $item = $subscriber->notificationItems()->sole();
        expect($item->title)->toBe('Yeni not: #git')
            ->and($item->body)->toBe('git switch - önceki dala döner.')
            ->and($item->digest_only)->toBeTrue()
            ->and($other->notificationItems()->count())->toBe(0);
    });

    it('tells about 30 days of a chain with the title each follower may read', function () {
        // 30 marked days ending today; today is unmarked and marked again to trigger the check.
        $chain = Goal::factory()->chain(str_repeat('x', 30))->censored()->create(['title' => 'Ekransız sabahlar']);
        follow($member = follower(), $chain);
        follow($close = follower('close'), $chain);

        $chain->chainDays()->whereDate('date', today())->delete();
        $chain->chainDays()->create(['date' => today(), 'state' => 'done']);

        expect($member->notificationItems()->sole()->title)->toBe('🔒 ██████: 🔥 30 gün!')
            ->and($member->notificationItems()->sole()->url)->toContain('/hedefler/zincir/k-'.$chain->id)
            ->and($close->notificationItems()->sole()->title)->toBe('Ekransız sabahlar: 🔥 30 gün!');
    });

    it('tells about a new record once per run', function () {
        // A 10-day run, a break, then a run that passes it.
        $chain = Goal::factory()->chain(str_repeat('x', 10).'-'.str_repeat('x', 11))->create(['title' => 'Kod']);
        follow($member = follower(), $chain);

        $chain->chainDays()->whereDate('date', today())->delete();
        $chain->chainDays()->create(['date' => today(), 'state' => 'done']);
        $chain->chainDays()->whereDate('date', today())->first()->update(['note' => 'yine']);

        expect($member->notificationItems()->where('title', 'like', '%rekor%')->count())->toBe(1);
    });

    it('stays silent about hidden goals', function () {
        $chain = Goal::factory()->chain(str_repeat('x', 29))->hidden()->create();
        follow($member = follower(), $chain);

        $chain->chainDays()->whereDate('date', today())->delete();
        $chain->chainDays()->create(['date' => today(), 'state' => 'done']);

        expect($member->notificationItems()->count())->toBe(0);
    });

    it('tells when a chain broke yesterday after a real streak', function () {
        $chain = Goal::factory()->chain('xxxxx--')->create(['title' => 'Spor']);
        follow($member = follower(), $chain);
        $chain->chainDays()->whereDate('date', today())->delete();

        $this->travelTo(CarbonImmutable::today()->addHours(1));
        $chain->chainDays()->whereDate('date', CarbonImmutable::yesterday())->delete();
        $this->artisan('notifications:announce --chain-breaks');

        expect($member->notificationItems()->sole()->title)->toBe('Spor: zincir koptu (5 gün sürdü)');
    });

    it('tells about half way and the goal being reached', function () {
        $goal = Goal::factory()->yearly()->create(['title' => '12 kitap oku', 'target' => 4]);
        follow($member = follower(), $goal);

        $goal->progressEntries()->create(['date' => today(), 'amount' => 2]);
        $goal->progressEntries()->create(['date' => today(), 'amount' => 2]);

        expect($member->notificationItems()->orderBy('id')->pluck('title')->all())->toBe(['12 kitap oku: yarısı tamam (2 / 4)', '12 kitap oku: BAŞARILDI']);
    });

    it('tells about milestones, long-term updates, devlog and project status', function () {
        $milestones = Goal::factory()->yearly(GoalMeasure::Milestones)->create();
        $milestone = $milestones->milestones()->create(['title' => 'Beta']);
        $longTerm = Goal::factory()->longTerm()->create();
        $project = Project::factory()->create(['status' => ProjectStatus::InProgress]);
        $member = follower();
        foreach ([$milestones, $longTerm, $project] as $followable) {
            follow($member, $followable);
        }

        $milestone->update(['done_at' => now()]);
        $longTerm->updates()->create(['date' => today(), 'body' => 'İlerleme var.']);
        $project->devlog()->create(['date' => today(), 'body' => 'Grafikler eklendi.']);
        $project->update(['status' => ProjectStatus::Live]);

        expect($member->notificationItems()->count())->toBe(5); // milestone, achieved (all milestones), update, devlog, status
    });

    it('tells the author of a comment about a reply, but not about their own', function () {
        $post = Post::factory()->create();
        $author = follower();
        $top = Comment::factory()->for($author)->create(['commentable_id' => $post->id]);

        Comment::factory()->create(['commentable_id' => $post->id, 'parent_id' => $top->id]);
        Comment::factory()->for($author)->create(['commentable_id' => $post->id, 'parent_id' => $top->id]);
        $pending = Comment::factory()->pending()->create(['commentable_id' => $post->id, 'parent_id' => $top->id]);
        expect($author->notificationItems()->count())->toBe(1);

        $pending->update(['approved_at' => now()]);
        expect($author->notificationItems()->count())->toBe(2);
    });
});

describe('sending', function () {
    it('sends instant members one e-mail with their items and marks them sent', function () {
        Mail::fake();
        $instant = follower(attributes: ['notification_frequency' => NotificationFrequency::Instant]);
        $daily = follower();
        foreach ([$instant, $daily] as $user) {
            NotificationItem::create(['user_id' => $user->id, 'key' => 'k1', 'title' => 'Bir şey oldu', 'url' => 'https://example.test']);
        }

        $this->artisan('notifications:send instant')->assertSuccessful();

        Mail::assertSent(NotificationDigest::class, 1);
        Mail::assertSent(NotificationDigest::class, fn (NotificationDigest $mail) => $mail->hasTo($instant->email) && $mail->items->count() === 1);
        expect($instant->notificationItems()->whereNull('sent_at')->count())->toBe(0)
            ->and($daily->notificationItems()->whereNull('sent_at')->count())->toBe(1);
    });

    it('keeps digest-only items of instant members for the daily digest', function () {
        Mail::fake();
        $instant = follower(attributes: ['notification_frequency' => NotificationFrequency::Instant]);
        $weekly = follower(attributes: ['notification_frequency' => NotificationFrequency::Weekly]);
        NotificationItem::create(['user_id' => $instant->id, 'key' => 'note', 'title' => 'Yeni not', 'url' => 'https://example.test', 'digest_only' => true]);
        NotificationItem::create(['user_id' => $weekly->id, 'key' => 'note', 'title' => 'Yeni not', 'url' => 'https://example.test', 'digest_only' => true]);

        $this->artisan('notifications:send instant')->assertSuccessful();

        Mail::assertNothingSent();

        $this->artisan('notifications:send daily')->assertSuccessful();

        Mail::assertSent(NotificationDigest::class, 1);
        Mail::assertSent(NotificationDigest::class, fn (NotificationDigest $mail) => $mail->hasTo($instant->email));
        expect($instant->notificationItems()->whereNull('sent_at')->count())->toBe(0)
            ->and($weekly->notificationItems()->whereNull('sent_at')->count())->toBe(1);
    });

    it('puts a one-click unsubscribe into every e-mail', function () {
        $user = follower();
        $item = NotificationItem::create(['user_id' => $user->id, 'key' => 'k', 'title' => 'Olay', 'url' => 'https://example.test']);

        $mail = new NotificationDigest($user, Collection::make([$item]));
        $headers = $mail->headers()->text;

        expect($headers['List-Unsubscribe-Post'])->toBe('List-Unsubscribe=One-Click')
            ->and($headers['List-Unsubscribe'])->toContain('/bildirimler/kapat/'.$user->id)->toContain('signature=');
        $mail->assertSeeInHtml('hiç e-posta gönderme')->assertSeeInText('Olay');
    });

    it('unsubscribes from everything through the signed link, without signing in', function () {
        $user = follower();
        $url = URL::signedRoute('notifications.unsubscribe', ['user' => $user->id]);

        $this->get($url)->assertOk()->assertSeeText('Evet, bırak');
        expect($user->fresh()->notification_frequency)->toBe(NotificationFrequency::Daily);

        $this->post($url)->assertOk();
        expect($user->fresh()->notification_frequency)->toBe(NotificationFrequency::Never);

        $this->post('/bildirimler/kapat/'.$user->id)->assertForbidden();
    });

    it('unfollows one thing through the signed link', function () {
        $user = follower();
        $film = Watchable::factory()->create(['title' => 'Dune']);
        follow($user, $film);
        $follow = $user->follows()->sole();

        $this->post(URL::signedRoute('follows.unsubscribe', ['follow' => $follow->id]))->assertOk()->assertSeeText('"Dune" artık takip listende değil');

        $this->assertModelMissing($follow);
    });
});

describe('account pages', function () {
    it('lists follows and lets the member drop one', function () {
        $user = follower();
        follow($user, Watchable::factory()->create(['title' => 'Shōgun']));
        follow($user, Goal::factory()->chain()->censored()->create(['title' => 'Gizli zincir']));
        $this->actingAs($user);

        $component = Livewire::test('pages::settings.follows')->assertSee('Shōgun')->assertSee('🔒 ██████')->assertDontSee('Gizli zincir');
        $component->call('unfollow', $user->follows()->first()->id);

        expect($user->follows()->count())->toBe(1);
    });

    it('switches the new notes subscription from the board', function () {
        $user = follower();
        $this->actingAs($user);

        Livewire::test('site.subscription', ['kind' => 'notes'])->call('toggle');

        expect($user->fresh()->notify_new_notes)->toBeTrue();
    });

    it('saves the new notes subscription with the other settings', function () {
        $user = follower();
        $this->actingAs($user);

        Livewire::test('pages::settings.notifications')->set('newNotes', true)->call('save')->assertHasNoErrors();

        expect($user->fresh()->notify_new_notes)->toBeTrue();
    });

    it('saves the frequency and the new posts subscription', function () {
        $user = follower();
        $this->actingAs($user);

        Livewire::test('pages::settings.notifications')->set('frequency', 'weekly')->set('newPosts', true)->call('save')->assertHasNoErrors();

        expect($user->fresh()->only(['notification_frequency', 'notify_new_posts']))->toBe(['notification_frequency' => NotificationFrequency::Weekly, 'notify_new_posts' => true]);
    });
});
