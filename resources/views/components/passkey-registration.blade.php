@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        getDefaultPasskeyName() {
            const ua = navigator.userAgent;

            const browser = [
                { pattern: /Edg|Edge/, name: 'Edge' },
                { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
                { pattern: /Firefox|FxiOS/, name: 'Firefox' },
                { pattern: /Chrome|CriOS/, name: 'Chrome' },
                { pattern: /Safari/, name: 'Safari' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            const os = [
                { pattern: /iPhone/, name: 'iPhone' },
                { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
                { pattern: /Android/, name: 'Android' },
                { pattern: /Mac/, name: 'Mac' },
                { pattern: /Windows/, name: 'Windows' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            return [browser, os].filter(Boolean).join(', ') || '';
        },
        init() {
            this.name = this.getDefaultPasskeyName();
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (!this.name.trim()) return;

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                this.name = '';
                this.showForm = false;
                await $wire.loadPasskeys();
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
        cancel() {
            this.showForm = false;
            this.name = '';
            this.error = null;
        },
    }"
>
    <template x-if="!supported">
        <p class="text-sm text-ink-faint">Bu tarayıcı passkey desteklemiyor.</p>
    </template>

    <template x-if="supported && !showForm">
        <div>
            <x-site.form.button variant="secondary" x-on:click="showForm = true">+ Passkey ekle</x-site.form.button>
        </div>
    </template>

    <template x-if="supported && showForm">
        <div class="space-y-4 rounded-md border-2 border-dashed border-rule p-4">
            <div class="space-y-1.5">
                <label for="passkey-name" class="block text-sm font-bold text-ink">Passkey adı</label>
                <input
                    id="passkey-name"
                    x-model="name"
                    placeholder="ör. Telefonum, iş bilgisayarı"
                    x-on:keydown.enter.prevent="register()"
                    x-ref="passkeyNameInput"
                    x-init="$nextTick(() => $refs.passkeyNameInput?.focus())"
                    class="block w-full rounded-md border-2 border-rule bg-paper px-3 py-2.5 text-ink focus:border-section-ink focus:outline-none"
                />
                <p class="text-sm text-ink-faint">Sonradan hangisi olduğunu tanıyabilmen için.</p>
            </div>

            <p x-show="error" x-text="error" x-cloak class="font-hand text-xl font-bold text-pen-red"></p>

            <div class="flex flex-wrap gap-3">
                <x-site.form.button x-on:click="register()" x-bind:disabled="loading || !name.trim()">
                    <span x-show="!loading">Kaydet</span>
                    <span x-show="loading" x-cloak>Kaydediliyor…</span>
                </x-site.form.button>
                <x-site.form.button variant="link" x-on:click="cancel()">Vazgeç</x-site.form.button>
            </div>
        </div>
    </template>
</div>
