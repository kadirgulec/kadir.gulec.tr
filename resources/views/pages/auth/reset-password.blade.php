<x-layouts::auth title="Yeni şifre" heading="Yeni şifreni belirle">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <x-site.form.input name="email" label="E-posta" type="email" :value="request('email')" required autocomplete="email" />
        <x-site.form.input name="password" label="Yeni şifre" type="password" required autocomplete="new-password" viewable />
        <x-site.form.input name="password_confirmation" label="Yeni şifre tekrar" type="password" required autocomplete="new-password" viewable />

        <x-site.form.button type="submit" class="w-full" data-test="reset-password-button">Şifreyi kaydet</x-site.form.button>
    </form>
</x-layouts::auth>
