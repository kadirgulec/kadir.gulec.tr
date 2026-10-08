@use('App\Enums\Section')

<x-layouts::site :section="Section::Notes" :title="$title" :draft="$note['isDraft']" :description="$note['text']" :og-image="$ogImage" og-type="article">
    <a href="{{ route('notes.index') }}" class="font-hand text-xl text-ink-soft hover:text-section-ink">← Öğrendiklerim</a>

    <div class="mx-auto mt-14 max-w-xl">
        <x-site.post-it :note="$note" size="lg" :linked="false" />

        <div class="mt-6 flex justify-end">
            <x-site.share-button />
        </div>
    </div>

    {{-- Older / newer --}}
    @if ($older || $newer)
        <nav class="mt-16 grid gap-4 border-t-2 border-dashed border-rule pt-8 sm:grid-cols-2" aria-label="Diğer notlar">
            @if ($older)
                <a href="{{ $older['url'] }}" class="group rounded-sm bg-paper-deep p-4">
                    <span class="font-hand text-lg text-ink-faint">← daha eski</span>
                    <span class="mt-1 line-clamp-2 block leading-snug group-hover:text-section-ink">{{ $older['text'] }}</span>
                </a>
            @endif

            @if ($newer)
                <a href="{{ $newer['url'] }}" class="group rounded-sm bg-paper-deep p-4 text-right sm:col-start-2">
                    <span class="font-hand text-lg text-ink-faint">daha yeni →</span>
                    <span class="mt-1 line-clamp-2 block leading-snug group-hover:text-section-ink">{{ $newer['text'] }}</span>
                </a>
            @endif
        </nav>
    @endif

    @if ($sameTag)
        <section class="mt-14" aria-labelledby="ayni-etiket">
            <h2 id="ayni-etiket" class="font-display text-2xl font-semibold">#{{ $note['tagName'] }} etiketli başka notlar</h2>

            <ul class="mt-10 grid items-start gap-x-8 gap-y-12 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($sameTag as $other)
                    <li><x-site.post-it :note="$other" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($posts)
        <section class="mt-14 max-w-2xl" aria-labelledby="bu-konuda">
            <h2 id="bu-konuda" class="font-display text-2xl font-semibold">Bu konuda yazdıklarım</h2>

            <ol class="mt-3">
                @foreach ($posts as $post)
                    <li>
                        <x-site.post-row :post="$post" class="py-2" />
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</x-layouts::site>
