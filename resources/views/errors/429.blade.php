<x-layouts::site :section="\App\Enums\Section::Home" title="Biraz yavaş" noindex>
    <x-site.torn-page code="429" heading="Biraz yavaş, kalem yetişemiyor.">
        <p>Çok kısa sürede çok fazla istek geldi. Bir dakika soluklan, sonra tekrar dene.</p>

        <x-slot:after>
            <x-site.error-links />
        </x-slot:after>
    </x-site.torn-page>
</x-layouts::site>
