<x-layouts::auth title="Giriş" heading="Tekrar hoş geldin" description="Defterin kenarına not düşmek için giriş yap.">
    <x-passkey-verify />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <x-site.form.input name="email" label="E-posta" type="email" :value="old('email')" required autofocus autocomplete="email" placeholder="ornek@eposta.com" />

        <div class="space-y-1.5">
            <x-site.form.input name="password" label="Şifre" type="password" required autocomplete="current-password" viewable />

            @if (Route::has('password.request'))
                <x-site.form.button variant="link" :href="route('password.request')">Şifremi unuttum</x-site.form.button>
            @endif
        </div>

        <x-site.form.checkbox name="remember" label="Beni hatırla" :checked="old('remember')" />

        <x-site.form.button type="submit" class="w-full" data-test="login-button">Giriş yap</x-site.form.button>
    </form>

    @if (Route::has('register'))
        <p class="text-center text-sm text-ink-soft">
            Hesabın yok mu?
            <x-site.form.button variant="link" :href="route('register')">Kayıt ol</x-site.form.button>
        </p>
    @endif
</x-layouts::auth>
