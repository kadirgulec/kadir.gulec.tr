<x-layouts::auth title="İki adımlı doğrulama">
    <div
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            toggle() {
                this.showRecoveryInput = ! this.showRecoveryInput;
                this.$nextTick(() => (this.showRecoveryInput ? this.$refs.recovery : this.$refs.code)?.querySelector('input')?.focus());
            },
        }"
        class="space-y-6"
    >
        <header class="space-y-2">
            <h1 class="font-display text-3xl font-extrabold tracking-tight" x-text="showRecoveryInput ? 'Kurtarma kodu' : 'Doğrulama kodu'">Doğrulama kodu</h1>
            <p class="text-ink-soft" x-show="! showRecoveryInput">Doğrulama uygulamandaki 6 haneli kodu yaz.</p>
            <p class="text-ink-soft" x-show="showRecoveryInput" x-cloak>Kurtarma kodlarından birini yaz. Her kod bir kere kullanılabilir.</p>
        </header>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-5">
            @csrf

            <div x-show="! showRecoveryInput" x-ref="code">
                <x-site.form.input name="code" label="Kod" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" autofocus x-bind:disabled="showRecoveryInput" class="[&_input]:text-center [&_input]:font-mono [&_input]:text-2xl [&_input]:tracking-[0.5em]" />
            </div>

            <div x-show="showRecoveryInput" x-cloak x-ref="recovery">
                <x-site.form.input name="recovery_code" label="Kurtarma kodu" autocomplete="one-time-code" x-bind:disabled="! showRecoveryInput" class="[&_input]:font-mono" />
            </div>

            <x-site.form.button type="submit" class="w-full">Devam et</x-site.form.button>
        </form>

        <p class="text-center text-sm text-ink-soft">
            <button type="button" x-on:click="toggle()" class="cursor-pointer underline decoration-section decoration-2 underline-offset-4 hover:text-ink">
                <span x-show="! showRecoveryInput">Kurtarma koduyla giriş yap</span>
                <span x-show="showRecoveryInput" x-cloak>Doğrulama koduyla giriş yap</span>
            </button>
        </p>
    </div>
</x-layouts::auth>
