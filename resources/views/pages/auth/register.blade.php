<x-layouts::auth title="Kayıt ol" heading="Deftere katıl" description="Yazılara yorum yazabilir, hedefleri ve filmleri takip edebilirsin.">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf

        <x-site.form.input name="name" label="Görünen ad" :value="old('name')" required autofocus autocomplete="name" hint="Yorumlarının yanında bu ad görünür." />
        <x-site.form.input name="email" label="E-posta" type="email" :value="old('email')" required autocomplete="email" placeholder="ornek@eposta.com" hint="Kimseye gösterilmez. Doğrulama ve bildirimler için." />
        <x-site.form.input name="password" label="Şifre" type="password" required autocomplete="new-password" viewable />
        <x-site.form.input name="password_confirmation" label="Şifre tekrar" type="password" required autocomplete="new-password" viewable />

        <x-site.form.button type="submit" class="w-full" data-test="register-user-button">Kayıt ol</x-site.form.button>
    </form>

    <p class="text-center text-sm text-ink-soft">
        Zaten hesabın var mı?
        <x-site.form.button variant="link" :href="route('login')">Giriş yap</x-site.form.button>
    </p>
</x-layouts::auth>
