<x-layouts::site :section="\App\Enums\Section::Home" title="Erişim yok" noindex>
    <x-site.torn-page code="403" heading="Bu sayfa kilitli bir çekmecede.">
        <p>Anahtarı bende, kusura bakma. Bu sayfayı görmek için yetkin yok.</p>

        <x-slot:after>
            <x-site.error-links />
        </x-slot:after>
    </x-site.torn-page>
</x-layouts::site>
