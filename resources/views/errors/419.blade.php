<x-layouts::site :section="\App\Enums\Section::Home" title="Oturumun süresi doldu" noindex>
    <x-site.torn-page code="419" heading="Kalemin mürekkebi kurumuş.">
        <p>Sayfa uzun süre açık kaldığı için oturumunun süresi doldu. Sayfayı yenileyip tekrar dene; yazdıklarını kaybettiysen kusura bakma.</p>

        <x-slot:after>
            <x-site.error-links />
        </x-slot:after>
    </x-site.torn-page>
</x-layouts::site>
