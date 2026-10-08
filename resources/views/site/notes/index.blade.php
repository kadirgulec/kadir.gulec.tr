@use('App\Enums\Section')

<x-layouts::site :section="Section::Notes" :title="$activeTag ? '#'.$activeTag['name'].' · Öğrendiklerim' : 'Öğrendiklerim'" description="Küçük notlar, büyük birikim: yol üstünde öğrendiğim küçük şeyler.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ $noteCount }} not</p>

    <h1 class="relative mt-3 inline-block font-display text-4xl leading-[1.08] font-extrabold tracking-tight text-balance sm:text-5xl">
        Küçük notlar, büyük birikim
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <p class="font-hand text-2xl text-ink-soft">yol üstünde öğrendiğim küçük şeyler</p>
        <div class="flex items-center gap-3">
            <livewire:site.subscription kind="notes" />
            <x-site.share-button />
        </div>
    </div>

    @if ($tags)
        <x-site.tag-filter route="notes.index" :tags="$tags" :active-tag="$activeTag" noun="notlar" />
    @endif

    @if ($notes->isEmpty())
        <p class="mt-14 font-hand text-2xl text-section-ink">Pano henüz boş, ilk not yakında.</p>
    @else
        {{-- The board: masonry columns, the content matters more than the order --}}
        <ol class="mt-14 columns-1 gap-x-8 sm:columns-2 xl:columns-3" aria-label="Notlar">
            @foreach ($notes as $note)
                <li class="mb-12 break-inside-avoid pt-3">
                    <x-site.post-it :note="$note" />
                </li>
            @endforeach
        </ol>

        <x-site.pagination :paginator="$notes" class="mt-4" />
    @endif
</x-layouts::site>
