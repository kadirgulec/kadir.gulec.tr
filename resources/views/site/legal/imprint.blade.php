@use('App\Enums\Section')

{{-- Imprint (Impressum). Kadir fills in the address in config/legal.php. --}}
<x-layouts::site :section="Section::Home" title="Künye">
    <article class="max-w-2xl">
        <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">künye · impressum</p>
        <h1 class="mt-3 font-display text-4xl font-extrabold tracking-tight sm:text-5xl">Künye</h1>

        <div class="prose-notebook mt-10">
            <p>Bu site özel, ticari olmayan bir kişisel defterdir.</p>

            <h2>Sorumlu (§ 5 DDG, § 18 Abs. 2 MStV)</h2>
            <p>
                {{ config('legal.name') }}<br>
                {!! nl2br(e(config('legal.address'))) !!}
            </p>

            <h2>İletişim</h2>
            <p>E-posta: <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a></p>

            <h2>İçerik ve linkler</h2>
            <p>Yazılar, yorumlar ve puanlar kişisel görüşümdür. Dış sitelere verdiğim linklerin içeriğinden o sitelerin sahipleri sorumludur. Üyelerin yorumlarından yazarları sorumludur; hukuka aykırı bir yorum görürsen bana yaz, kaldırırım.</p>
            <p>Film ve dizi bilgileri: <a href="https://www.themoviedb.org" target="_blank" rel="noopener">The Movie Database (TMDB)</a>. Bu site TMDB tarafından onaylanmış veya desteklenmemektedir.</p>
        </div>
    </article>
</x-layouts::site>
