<?php

use App\Actions\Push\SavePushDevice;
use App\Enums\NotificationFrequency;
use App\Listeners\ForgetPushDevice;
use App\Models\PushSubscription;
use App\Support\Push\PushNotifier;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account'), Title('Bildirimler')] class extends Component {
    public string $frequency = 'daily';

    public bool $newPosts = false;

    public bool $newNotes = false;

    public bool $newReviews = false;

    public ?string $status = null;

    public function mount(): void
    {
        $this->frequency = auth()->user()->notification_frequency->value;
        $this->newPosts = auth()->user()->notify_new_posts;
        $this->newNotes = auth()->user()->notify_new_notes;
        $this->newReviews = auth()->user()->notify_monthly_reviews;
    }

    public function save(): void
    {
        $this->validate(['frequency' => ['required', Rule::enum(NotificationFrequency::class)], 'newPosts' => ['boolean'], 'newNotes' => ['boolean'], 'newReviews' => ['boolean']]);

        auth()->user()->forceFill([
            'notification_frequency' => $this->frequency,
            'notify_new_posts' => $this->newPosts,
            'notify_new_notes' => $this->newNotes,
            'notify_monthly_reviews' => $this->newReviews,
        ])->save();

        $this->status = 'Kaydedildi.';
    }

    /**
     * This device's push subscription (PushSubscription::toJSON() in the browser).
     * Called again on every visit, so a device that changed hands follows its new owner.
     *
     * @param  array<string, mixed>  $subscription
     */
    public function enablePush(array $subscription, SavePushDevice $savePushDevice): void
    {
        $savePushDevice->handle(auth()->user(), $subscription, request()->userAgent());
    }

    public function disablePush(string $endpoint): void
    {
        auth()->user()->pushSubscriptions()->where('endpoint_hash', PushSubscription::hashOf($endpoint))->delete();
        Cookie::queue(Cookie::forget(ForgetPushDevice::COOKIE));
    }

    public function testPush(PushNotifier $push): void
    {
        $push->toUser(auth()->user(), [
            'title' => 'Bildirimler açık ✓',
            'body' => 'Bir e-posta geldiğinde bu cihaza da haber gelecek.',
            'url' => route('notifications.edit'),
            'tag' => 'test',
        ]);
    }
}; ?>

<div class="space-y-6">
    <header class="space-y-1">
        <h1 class="font-display text-4xl font-extrabold tracking-tight">Bildirimler</h1>
        <p class="text-ink-soft">Takip ettiklerinde bir şey olunca ne sıklıkla e-posta istersin?</p>
    </header>

    <form wire:submit="save" class="space-y-6">
        <fieldset class="space-y-3">
            <legend class="sr-only">Sıklık</legend>
            @foreach (NotificationFrequency::cases() as $option)
                <label class="flex cursor-pointer items-center gap-3" wire:key="frequency-{{ $option->value }}">
                    <input type="radio" wire:model="frequency" value="{{ $option->value }}" class="size-4 accent-(--color-section-ink)" />
                    <span>{{ $option->label() }}</span>
                </label>
            @endforeach
        </fieldset>

        <x-site.form.checkbox wire:model="newPosts" label="Yeni yazı çıkınca haber ver" />
        <x-site.form.checkbox wire:model="newNotes" label="Yeni öğrendiklerimi özetle gönder" />
        <x-site.form.checkbox wire:model="newReviews" label="Aylık değerlendirmem yayınlanınca haber ver" />

        <p class="text-sm text-ink-faint">Yorumuna cevap gelince de aynı sıklıkla haber verilir. Öğrendiklerim küçük ve sık olduğu için, "hemen" seçsen bile günlük özetle gelir.</p>

        <div class="flex items-center gap-4">
            <x-site.form.button type="submit">Kaydet</x-site.form.button>
            <x-site.form.status :message="$status" />
        </div>
    </form>

    @if (PushNotifier::publicKey())
        {{-- Push on this device: the browser holds the subscription, the server a copy for sending. --}}
        <section
            class="space-y-3 border-t border-dashed border-rule pt-6"
            aria-labelledby="bu-cihaz"
            wire:ignore
            x-cloak
            x-data="{
                state: 'loading',
                async init() {
                    if (! window.kgPush?.supported) { this.state = 'unsupported'; return }
                    if (Notification.permission === 'denied') { this.state = 'denied'; return }
                    await window.kgPush.ready
                    const current = await window.kgPush.current()
                    if (current) await $wire.enablePush(window.kgPush.serialize(current))
                    this.state = current ? 'on' : 'off'
                },
                async enable() {
                    this.state = 'busy'
                    try {
                        const subscription = await window.kgPush.subscribe(@js(PushNotifier::publicKey()))
                        if (subscription) await $wire.enablePush(subscription)
                        this.state = subscription ? 'on' : (Notification.permission === 'denied' ? 'denied' : 'off')
                    } catch (error) {
                        this.state = 'failed'
                    }
                },
                async disable() {
                    this.state = 'busy'
                    const endpoint = await window.kgPush.unsubscribe()
                    if (endpoint) await $wire.disablePush(endpoint)
                    this.state = 'off'
                },
            }"
        >
            <h2 id="bu-cihaz" class="font-display text-2xl font-semibold">Bu cihaz</h2>
            <p class="text-ink-soft">Sana bir e-posta gittiğinde bu cihaza da bildirim gelsin. Bu cihazda çıkış yapınca durur, yeniden girince kendiliğinden açılır. Oturumun süresi dolsa da gelmeye devam eder; dokununca önce giriş yapman istenir.</p>

            <div class="flex flex-wrap items-center gap-4">
                <x-site.form.button x-show="state === 'off' || state === 'failed'" x-on:click="enable">Bu cihazda bildirimleri aç</x-site.form.button>
                <x-site.form.button x-show="state === 'busy'" disabled>Bekle…</x-site.form.button>
                <template x-if="state === 'on'">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="font-hand text-xl text-section-ink">✓ bu cihazda açık</span>
                        <x-site.form.button variant="secondary" x-on:click="$wire.testPush()">Deneme bildirimi gönder</x-site.form.button>
                        <x-site.form.button variant="link" x-on:click="disable">Kapat</x-site.form.button>
                    </div>
                </template>
            </div>

            <p x-show="state === 'failed'" class="text-sm text-pen-red">Bildirim açılamadı. Sayfayı yenileyip tekrar dene.</p>
            <p x-show="state === 'denied'" class="text-sm text-ink-faint">Bu sitenin bildirimleri tarayıcıda engellenmiş. Tarayıcının site ayarlarından izin verirsen burada açabilirsin.</p>
            <p x-show="state === 'unsupported'" class="text-sm text-ink-faint">Bu tarayıcı bildirim desteklemiyor. iPhone ya da iPad'de önce siteyi ana ekrana ekle (Paylaş → Ana Ekrana Ekle), sonra oradan aç.</p>
        </section>
    @endif
</div>
