@use('App\Enums\Section')

<x-layouts::site :section="Section::Notes" title="Öğrendiklerim" description="Küçük notlar, büyük birikim: yol üstünde öğrendiğim küçük şeyler.">
    <h1 class="relative inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        Küçük notlar, büyük birikim
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <p class="mt-8 font-hand text-2xl text-ink-soft">yol üstünde öğrendiğim küçük şeyler</p>

    <p class="mt-14 font-hand text-2xl text-section-ink">Pano henüz boş, ilk not yakında.</p>
</x-layouts::site>
