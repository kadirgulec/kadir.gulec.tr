@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => 'Passkey ile giriş yap',
    'loadingLabel' => 'Doğrulanıyor…',
    'separator' => 'ya da e-postayla',
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <x-site.form.button variant="secondary" class="w-full" x-on:click="verify()" x-bind:disabled="loading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">{!! \App\Support\LucideIcons::markup('fingerprint') !!}</svg>
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </x-site.form.button>
                <p x-show="error" x-text="error" x-cloak class="text-center font-hand text-xl font-bold text-pen-red"></p>
            </div>

            <div class="my-6 flex items-center gap-3 font-hand text-lg text-ink-faint" aria-hidden="true">
                <span class="h-px flex-1 border-t border-dashed border-rule"></span>
                {{ $separator }}
                <span class="h-px flex-1 border-t border-dashed border-rule"></span>
            </div>
        </div>
    </template>
</div>
