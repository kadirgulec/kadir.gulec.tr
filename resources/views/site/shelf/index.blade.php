@use('App\Enums\Section')

@php
    $coverTilts = [-3, 2, -1.5, 3, -2, 1.5];
@endphp

<x-layouts::site :section="Section::Shelf" title="Rafım" description="İzlediğim filmler ve diziler, okuduğum kitaplar: hepsi aynı rafta, tek bir günlükte.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">bu yıl {{ $filmCountThisYear }} film · {{ $seriesCountThisYear }} dizi · {{ $bookCountThisYear }} kitap</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        Rafım
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <p class="font-hand text-2xl text-ink-soft">izlediklerim ve okuduklarım, aynı rafta</p>
        <x-site.share-button />
    </div>

    <x-site.shelf-tabs current="all" class="mt-8" />

    {{-- What is on the nightstand right now --}}
    <div class="mt-14 grid gap-12 lg:grid-cols-2">
        <section class="min-w-0" aria-labelledby="su-an-okuyorum">
            <h2 id="su-an-okuyorum" class="font-display text-2xl font-semibold">Şu an okuyorum</h2>

            @if ($currentlyReading)
                <ul class="mt-6 grid grid-cols-1 gap-5">
                    @foreach ($currentlyReading as $book)
                        <x-site.reading-card :book="$book" />
                    @endforeach
                </ul>
            @else
                <p class="mt-4 font-hand text-2xl text-section-ink">Şu an elimde kitap yok.</p>
            @endif
        </section>

        <section class="min-w-0" aria-labelledby="su-an-izliyorum">
            <h2 id="su-an-izliyorum" class="font-display text-2xl font-semibold">Şu an izliyorum</h2>

            @if ($currentlyWatching)
                <ul class="mt-6 grid grid-cols-1 gap-5">
                    @foreach ($currentlyWatching as $series)
                        <x-site.watching-card :series="$series" />
                    @endforeach
                </ul>
            @else
                <p class="mt-4 font-hand text-2xl text-section-ink">Şu an yarım kalmış bir dizi yok.</p>
            @endif
        </section>
    </div>

    {{-- What comes next: the first three of each list --}}
    @if ($toRead !== [] || $watchlist !== [])
        <div class="mt-16 grid gap-12 lg:grid-cols-2">
            @if ($toRead !== [])
                <section class="min-w-0" aria-labelledby="okuyacaklarim">
                    <h2 id="okuyacaklarim" class="font-display text-2xl font-semibold">Okuyacaklarım</h2>

                    <ol class="-mx-2 mt-4 flex gap-6 overflow-x-auto px-2 pt-4 pb-6">
                        @foreach (array_slice($toRead, 0, 3) as $index => $entry)
                            <x-site.to-read-entry :entry="$entry" :tilt="$coverTilts[($index + 2) % count($coverTilts)]" />
                        @endforeach
                    </ol>

                    <a href="{{ route('books.index') }}#sirada" class="font-hand text-2xl text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">hepsi ({{ count($toRead) }}) →</a>
                </section>
            @endif

            @if ($watchlist !== [])
                <section class="min-w-0" aria-labelledby="izleyeceklerim">
                    <h2 id="izleyeceklerim" class="font-display text-2xl font-semibold">İzleyeceklerim</h2>

                    <ol class="-mx-2 mt-4 flex gap-6 overflow-x-auto px-2 pt-4 pb-6">
                        @foreach (array_slice($watchlist, 0, 3) as $index => $entry)
                            <x-site.watchlist-entry :entry="$entry" :tilt="$coverTilts[($index + 4) % count($coverTilts)]" />
                        @endforeach
                    </ol>

                    <a href="{{ route('watched.index') }}#sirada" class="font-hand text-2xl text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">hepsi ({{ count($watchlist) }}) →</a>
                </section>
            @endif
        </div>
    @endif

    {{-- One diary for both: viewings, finished and abandoned books, grouped by month --}}
    <section class="mt-16" aria-labelledby="gunluk">
        <h2 id="gunluk" class="font-display text-2xl font-semibold">Günlük</h2>

        @foreach ($diaryByMonth as $items)
            <div class="mt-8">
                <div class="flex items-baseline gap-3 border-b-2 border-dashed border-rule pb-2">
                    <h3 class="font-display text-xl font-semibold">{{ $items->first()['date']->locale('tr')->translatedFormat('F Y') }}</h3>
                    <span class="font-mono text-xs text-ink-faint">{{ $items->count() }} kayıt</span>
                </div>

                <ol class="mt-5 flex flex-col gap-5">
                    @foreach ($items as $item)
                        <li class="grid grid-cols-[2.25rem_1fr] gap-3 sm:grid-cols-[3.5rem_1fr] sm:gap-4">
                            <div class="pt-3 text-center">
                                <span class="block font-display text-2xl leading-none font-semibold">{{ $item['date']->day }}</span>
                                <span class="font-mono text-[10px] text-ink-faint uppercase">{{ $item['date']->locale('tr')->translatedFormat('M') }}</span>
                            </div>

                            @if ($item['kind'] === 'book')
                                <x-site.book-diary-card :entry="$item['entry']" />
                            @else
                                <x-site.watched-diary-card :entry="$item['entry']" />
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
        @if (! $diaryByMonth)
            <p class="mt-6 font-hand text-2xl text-section-ink">Raf henüz boş.</p>
        @endif
    </section>

    <x-site.tmdb-attribution compact class="mt-16" />
</x-layouts::site>
