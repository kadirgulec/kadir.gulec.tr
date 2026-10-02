{{--
    Spam protection of public forms: a honeypot field and the Cloudflare
    Turnstile widget. In plain forms the widget writes its token into
    "cf-turnstile-response"; with "model" it hands the token to a Livewire
    property instead and resets itself after every submit ("turnstile-reset").
--}}
@props([
    'model' => null,
])

@php
    $siteKey = config('services.turnstile.site_key');
@endphp

<div {{ $attributes->class('space-y-2') }}>
    {{-- Honeypot: invisible to people, tempting to bots --}}
    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
        <label for="hp-{{ \App\Rules\Honeypot::FIELD }}">Web siten (boş bırak)</label>
        <input type="text" id="hp-{{ \App\Rules\Honeypot::FIELD }}" name="{{ \App\Rules\Honeypot::FIELD }}" tabindex="-1" autocomplete="off" @if ($model) wire:model="{{ \App\Rules\Honeypot::FIELD }}" @endif />
    </div>

    @if (filled($siteKey))
        <div
            wire:ignore
            x-data="{
                widget: null,
                render() {
                    if (! window.turnstile) return setTimeout(() => this.render(), 200);
                    this.widget = window.turnstile.render(this.$refs.box, {
                        sitekey: @js($siteKey),
                        language: 'tr',
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        @if ($model)
                            callback: (token) => $wire.set(@js($model), token, false),
                            'expired-callback': () => $wire.set(@js($model), '', false),
                        @endif
                    });
                },
            }"
            x-init="render()"
            @if ($model) x-on:turnstile-reset.window="window.turnstile?.reset(widget)" @endif
        >
            <div x-ref="box"></div>
        </div>

        @once
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
        @endonce
    @endif

    <x-site.form.error :message="$errors->first($model ?? 'cf-turnstile-response')" />
</div>
