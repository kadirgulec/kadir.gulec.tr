@php($suggestion = \App\Support\NotFoundSuggestion::for(request()->path()))

<x-layouts::site :section="\App\Enums\Section::Home" title="Sayfa bulunamadı" noindex>
    <x-site.torn-page code="404" heading="Buraya bir şey yazacaktım…">
        <p>Galiba bu sayfayı defterden koparmışım. Ya adres yanlış yazıldı ya da burada hiç sayfa olmadı.</p>

        @if ($suggestion)
            <p class="font-hand text-2xl text-ink">
                Bunu mu aradın?
                <a href="{{ $suggestion['url'] }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">{{ $suggestion['title'] }}</a>
            </p>
        @endif

        <x-slot:after>
            <x-site.error-links />
        </x-slot:after>
    </x-site.torn-page>
</x-layouts::site>
