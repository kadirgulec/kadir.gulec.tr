<x-layouts::auth title="Şifremi unuttum" heading="Şifreni mi unuttun?" description="E-posta adresini yaz, yeni şifre belirlemen için bir link gönderelim.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-site.form.input name="email" label="E-posta" type="email" :value="old('email')" required autofocus autocomplete="email" placeholder="ornek@eposta.com" />

        <x-site.form.button type="submit" class="w-full" data-test="email-password-reset-link-button">Linki gönder</x-site.form.button>
    </form>

    <p class="text-center text-sm text-ink-soft">
        Hatırladın mı?
        <x-site.form.button variant="link" :href="route('login')">Giriş yap</x-site.form.button>
    </p>
</x-layouts::auth>
