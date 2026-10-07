<?php

use App\Actions\Contact\SendContactMessage;
use App\Enums\ChainPeriod;
use App\Enums\NotificationFrequency;
use App\Listeners\ForgetPushDevice;
use App\Models\Goal;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Push\PushNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Records what would have gone to the push services.
 */
class RecordingPushNotifier extends PushNotifier
{
    /** @var list<array<string, mixed>> */
    public array $sent = [];

    protected function deliver(Collection $subscriptions, array $message): void
    {
        $this->sent[] = [...$message, 'endpoints' => $subscriptions->pluck('endpoint')->sort()->values()->all()];
    }
}

function pushDevice(User $user, string $name = 'telefon'): PushSubscription
{
    $endpoint = 'https://push.example.test/'.$user->id.'-'.$name;

    $device = new PushSubscription(['endpoint' => $endpoint, 'endpoint_hash' => PushSubscription::hashOf($endpoint), 'public_key' => 'p256dh-key', 'auth_token' => 'auth-key']);
    $device->user_id = $user->id;
    $device->save();

    return $device;
}

function browserSubscription(string $endpoint = 'https://push.example.test/yeni'): array
{
    return ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-key'], 'contentEncoding' => 'aes128gcm'];
}

beforeEach(function () {
    config(['services.webpush.public_key' => 'public-test-key', 'services.webpush.private_key' => 'private-test-key']);
    $this->app->instance(PushNotifier::class, $this->push = new RecordingPushNotifier);
});

describe('installable app', function () {
    it('serves a manifest with its icons', function () {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk()->assertHeader('Content-Type', 'application/manifest+json')->assertJsonPath('short_name', 'kg');

        foreach ($response->json('icons') as $icon) {
            expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
        }

        expect(public_path('sw.js'))->toBeFile()->and(public_path('offline.html'))->toBeFile();
    });

    it('links the manifest and names a verified member to the app for push', function () {
        $this->get('/')->assertSee('rel="manifest"', false)->assertDontSee('name="kg-push"', false);

        $this->actingAs(User::factory()->member()->unverified()->create())->get('/')->assertDontSee('name="kg-push"', false);

        $member = User::factory()->member()->create();
        $this->actingAs($member)->get('/')->assertSee('name="kg-push" content="'.$member->id.'"', false);
    });
});

describe('devices', function () {
    it('switches push on for this device and remembers which one it is', function () {
        $member = User::factory()->member()->create();
        $this->actingAs($member);

        Livewire::test('pages::settings.notifications')->assertSee('Bu cihaz')->call('enablePush', browserSubscription())->assertHasNoErrors();

        expect($member->pushSubscriptions()->sole()->endpoint)->toBe('https://push.example.test/yeni');
    });

    it('moves a device to whoever signed in on it last', function () {
        $device = pushDevice($previous = User::factory()->member()->create());
        $this->actingAs($next = User::factory()->member()->create());

        Livewire::test('pages::settings.notifications')->call('enablePush', browserSubscription($device->endpoint));

        expect($previous->pushSubscriptions()->count())->toBe(0)
            ->and($next->pushSubscriptions()->count())->toBe(1);
    });

    it('accepts only secure push addresses', function () {
        $this->actingAs(User::factory()->member()->create());

        Livewire::test('pages::settings.notifications')
            ->call('enablePush', browserSubscription('http://push.example.test/acik'))
            ->assertHasErrors('endpoint');

        expect(PushSubscription::count())->toBe(0);
    });

    it('switches push off for one device only', function () {
        $phone = pushDevice($member = User::factory()->member()->create());
        pushDevice($member, 'laptop');
        $this->actingAs($member);

        Livewire::test('pages::settings.notifications')->call('disablePush', $phone->endpoint);

        expect($member->pushSubscriptions()->pluck('endpoint')->all())->toBe(['https://push.example.test/'.$member->id.'-laptop']);
    });

    it('forgets the device on logout', function () {
        $phone = pushDevice($member = User::factory()->member()->create());
        pushDevice($member, 'laptop');

        $this->actingAs($member)->withCookie(ForgetPushDevice::COOKIE, $phone->endpoint_hash)->post(route('logout'))
            ->assertCookie(ForgetPushDevice::NOTICE_COOKIE);

        expect($member->pushSubscriptions()->count())->toBe(1);
    });

    it('has the browser unsubscribe on the page after a logout, once', function () {
        $this->withCookie(ForgetPushDevice::NOTICE_COOKIE, '1')->get('/')
            ->assertSee('name="kg-push-forget"', false)
            ->assertCookieExpired(ForgetPushDevice::NOTICE_COOKIE);
    });

    it('keeps push on when a session merely ran out', function () {
        $this->get('/')->assertDontSee('name="kg-push-forget"', false);
    });

    it('saves the device the app switches push back on for', function () {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->postJson(route('push-devices.store'), browserSubscription())
            ->assertNoContent()
            ->assertCookie(ForgetPushDevice::COOKIE, PushSubscription::hashOf('https://push.example.test/yeni'));

        expect($member->pushSubscriptions()->sole()->endpoint)->toBe('https://push.example.test/yeni');
    });

    it('refuses a device from the app with an insecure push address', function () {
        $this->actingAs(User::factory()->member()->create())
            ->postJson(route('push-devices.store'), browserSubscription('http://push.example.test/acik'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');

        expect(PushSubscription::count())->toBe(0);
    });

    it('saves no device from the app for a guest', function () {
        $this->postJson(route('push-devices.store'), browserSubscription())->assertUnauthorized();

        expect(PushSubscription::count())->toBe(0);
    });

    it('hides the device section while push has no keys', function () {
        config(['services.webpush.public_key' => null]);
        $this->actingAs(User::factory()->member()->create());

        Livewire::test('pages::settings.notifications')->assertDontSee('Bu cihaz');
    });
});

describe('a push with every e-mail', function () {
    it('sends one push per digest, leading to the one item or to the follows', function () {
        Mail::fake();
        $member = User::factory()->member()->create(['notification_frequency' => NotificationFrequency::Instant]);
        pushDevice($member);
        $member->notificationItems()->create(['key' => 'a', 'title' => 'Spor: 🔥 10 hafta!', 'url' => 'https://kadir.test/hedefler/zincir/spor']);

        $this->artisan('notifications:send instant');

        expect($this->push->sent)->toHaveCount(1)
            ->and($this->push->sent[0])->toMatchArray(['title' => 'Spor: 🔥 10 hafta!', 'url' => 'https://kadir.test/hedefler/zincir/spor']);

        $member->notificationItems()->create(['key' => 'b', 'title' => 'Bir', 'url' => 'https://kadir.test/1']);
        $member->notificationItems()->create(['key' => 'c', 'title' => 'İki', 'url' => 'https://kadir.test/2']);
        $this->artisan('notifications:send instant');

        expect($this->push->sent[1])->toMatchArray(['title' => 'Defterde 2 yeni şey', 'body' => "· Bir\n· İki", 'url' => route('follows.index')]);
    });

    it('sends no push to members who get no e-mail', function () {
        Mail::fake();
        $member = User::factory()->member()->unverified()->create(['notification_frequency' => NotificationFrequency::Instant]);
        pushDevice($member);
        $member->notificationItems()->create(['key' => 'a', 'title' => 'Bir', 'url' => 'https://kadir.test/1']);

        $this->artisan('notifications:send instant');

        expect($this->push->sent)->toBe([]);
    });

    it('sends chain reminders and contact messages to the devices of whoever may act on them', function () {
        Mail::fake();
        config(['mail.contact_to' => 'kadir@example.test']);
        $admin = User::factory()->admin()->create();
        pushDevice($admin);
        pushDevice(User::factory()->member()->create());

        $this->travelTo(CarbonImmutable::parse('2026-10-08 08:00'));
        Goal::factory()->chain('')->per(ChainPeriod::Week, 2)->create(['title' => 'Spor', 'started_on' => '2026-09-28']);
        $this->artisan('chains:remind');

        app(SendContactMessage::class)->handle('Ayşe', 'ayse@example.test', 'Merhaba!', '127.0.0.1');

        $adminDevice = ['https://push.example.test/'.$admin->id.'-telefon'];

        expect($this->push->sent)->toHaveCount(2)
            ->and($this->push->sent[0])->toMatchArray(['title' => 'Spor: bu hafta 0/2, 4 günde 2 kez daha', 'url' => route('admin.dashboard'), 'endpoints' => $adminDevice])
            ->and($this->push->sent[1])->toMatchArray(['title' => 'İletişim formu: Ayşe', 'body' => 'Merhaba!', 'endpoints' => $adminDevice]);
    });

    it('stays quiet without VAPID keys', function () {
        Mail::fake();
        config(['services.webpush.private_key' => null]);
        $member = User::factory()->member()->create(['notification_frequency' => NotificationFrequency::Instant]);
        pushDevice($member);
        $member->notificationItems()->create(['key' => 'a', 'title' => 'Bir', 'url' => 'https://kadir.test/1']);

        $this->artisan('notifications:send instant');

        expect($this->push->sent)->toBe([]);
    });
});
