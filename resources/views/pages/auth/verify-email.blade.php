<x-layouts::auth title="E-posta doğrulama" heading="E-postanı kontrol et" description="Kayıt olurken yazdığın adrese bir doğrulama linki gönderdik. Linke tıklayınca hesabın açılır.">
    @if (session('status') === 'verification-link-sent')
        <x-site.form.status message="Yeni bir doğrulama linki gönderdik." />
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-site.form.button type="submit">Linki tekrar gönder</x-site.form.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-site.form.button type="submit" variant="link" data-test="logout-button">Çıkış yap</x-site.form.button>
        </form>
    </div>
</x-layouts::auth>
