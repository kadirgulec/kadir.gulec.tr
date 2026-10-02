<x-layouts::auth title="Şifreni onayla" heading="Bir saniye" description="Bu bölüm hassas. Devam etmek için şifreni ya da passkey'ini kullan.">
    <x-passkey-verify
        options-route="passkey.confirm-options"
        submit-route="passkey.confirm"
        label="Passkey ile onayla"
        separator="ya da şifreyle"
    />

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf

        <x-site.form.input name="password" label="Şifre" type="password" required autofocus autocomplete="current-password" viewable />

        <x-site.form.button type="submit" class="w-full" data-test="confirm-password-button">Onayla</x-site.form.button>
    </form>
</x-layouts::auth>
