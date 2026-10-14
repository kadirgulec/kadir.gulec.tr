@use('App\Enums\Section')

@php
    $posterTilts = [-3, 2, -1.5, 3, -2, 1.5];
@endphp

<x-layouts::site :section="Section::Shelf" title="İzlediklerim" description="İzlediğim filmler ve diziler: puanlar, kısa yorumlar ve bir izleme günlüğü.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">bu yıl {{ $filmCountThisYear }} film · {{ $seriesCountThisYear }} dizi</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        İzlediklerim
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <p class="font-hand text-2xl text-ink-soft">puanlar 10 üzerinden · yıldızlılar favorilerim · ✍️ olanlarda yorumum var</p>
        <x-site.share-button />
    </div>

    <x-site.shelf-tabs current="watched" class="mt-8" />

    {{-- Poster strip --}}
    <section class="mt-14" aria-labelledby="son-izlediklerim">
        <h2 id="son-izlediklerim" class="font-display text-2xl font-semibold">Son izlediklerim</h2>

        <ul class="-mx-2 mt-4 flex snap-x gap-7 overflow-x-auto px-2 pt-6 pb-8 sm:grid sm:grid-cols-3 sm:overflow-visible lg:grid-cols-6 lg:gap-6">
            @foreach ($recent as $index => $entry)
                <li class="relative w-32 shrink-0 snap-start sm:w-auto">
                    <a href="{{ $entry['url'] }}" class="group block" aria-label="{{ $entry['title'] }}">
                        <x-site.poster
                            :title="$entry['title']"
                            :year="$entry['year']"
                            :image-url="$entry['posterUrl']"
                            :colors="$entry['posterColors']"
                            :tilt="$posterTilts[$index % count($posterTilts)]"
                            :caption="$entry['watchedAt']->locale('tr')->translatedFormat('j M')"
                        />
                    </a>

                    @if ($entry['rating'] !== null)
                        <x-site.grade :value="$entry['rating']" size="sm" class="pointer-events-none absolute -right-3 bottom-12 rounded-full bg-paper/85" />
                    @endif

                    @if ($entry['isFavorite'])
                        <x-site.favorite-star class="pointer-events-none absolute -top-3 -left-3 size-8" />
                    @endif

                    @if ($entry['hasReview'])
                        <span class="pointer-events-none absolute top-3 -right-3 rotate-6 rounded-full bg-section px-2 py-0.5 font-hand text-base leading-tight font-bold text-section-on shadow-sm">✍️ yorum</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Currently watching --}}
    <section class="mt-6" aria-labelledby="su-an-izliyorum">
        <h2 id="su-an-izliyorum" class="font-display text-2xl font-semibold">Şu an izliyorum</h2>

        <ul class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($currentlyWatching as $series)
                <x-site.watching-card :series="$series" />
            @endforeach
        </ul>
    </section>

    {{-- Watchlist: the first six, the rest behind "hepsini göster" (a plain <details>, no script needed) --}}
    @if ($watchlist !== [])
        @php
            [$watchlistFirst, $watchlistRest] = [array_slice($watchlist, 0, 6), array_slice($watchlist, 6)];
            $stripClasses = '-mx-2 flex snap-x gap-6 overflow-x-auto px-2 pt-4 pb-6 sm:flex-wrap sm:overflow-visible';
        @endphp

        <section class="mt-16" aria-labelledby="sirada">
            <h2 id="sirada" class="font-display text-2xl font-semibold">Sırada</h2>
            <p class="font-hand text-xl text-ink-faint">izleyeceklerim, sırasıyla</p>

            <ol class="mt-4 {{ $stripClasses }}">
                @foreach ($watchlistFirst as $index => $entry)
                    <x-site.watchlist-entry :entry="$entry" :tilt="$posterTilts[($index + 2) % count($posterTilts)]" />
                @endforeach
            </ol>

            @if ($watchlistRest !== [])
                <details class="group">
                    <summary class="inline-block cursor-pointer list-none font-hand text-2xl text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none] [&::-webkit-details-marker]:hidden">
                        <span class="group-open:hidden">hepsini göster ({{ count($watchlistRest) }} tane daha)</span>
                        <span class="hidden group-open:inline">daha az göster</span>
                    </summary>

                    <ol start="7" class="mt-2 {{ $stripClasses }}">
                        @foreach ($watchlistRest as $index => $entry)
                            <x-site.watchlist-entry :entry="$entry" :tilt="$posterTilts[($index + 8) % count($posterTilts)]" />
                        @endforeach
                    </ol>
                </details>
            @endif
        </section>
    @endif

    {{-- Diary --}}
    <section class="mt-16" aria-labelledby="gunluk">
        <h2 id="gunluk" class="font-display text-2xl font-semibold">Günlük</h2>

        @foreach ($diaryByMonth as $entries)
            <div class="mt-8">
                <div class="flex items-baseline gap-3 border-b-2 border-dashed border-rule pb-2">
                    <h3 class="font-display text-xl font-semibold">{{ $entries->first()['watchedAt']->locale('tr')->translatedFormat('F Y') }}</h3>
                    <span class="font-mono text-xs text-ink-faint">{{ $entries->count() }} kayıt</span>
                </div>

                <ol class="mt-5 flex flex-col gap-5">
                    @foreach ($entries as $entry)
                        <li class="grid grid-cols-[2.25rem_1fr] gap-3 sm:grid-cols-[3.5rem_1fr] sm:gap-4">
                            <div class="pt-3 text-center">
                                <span class="block font-display text-2xl leading-none font-semibold">{{ $entry['watchedAt']->day }}</span>
                                <span class="font-mono text-[10px] text-ink-faint uppercase">{{ $entry['watchedAt']->locale('tr')->translatedFormat('M') }}</span>
                            </div>

                            <x-site.watched-diary-card :entry="$entry" />
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
        @if (! $diaryByMonth)
            <p class="mt-6 font-hand text-2xl text-section-ink">Günlük henüz boş, patlamış mısır hazırlanıyor.</p>
        @endif
    </section>

    <x-site.tmdb-attribution compact class="mt-16" />
</x-layouts::site>
