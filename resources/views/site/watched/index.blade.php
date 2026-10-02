@use('App\Enums\Section')

@php
    $posterTilts = [-3, 2, -1.5, 3, -2, 1.5];
@endphp

<x-layouts::site :section="Section::Watched" title="İzlediklerim">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">bu yıl {{ $filmCountThisYear }} film · {{ $seriesCountThisYear }} dizi</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        İzlediklerim
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <p class="mt-8 font-hand text-2xl text-ink-soft">puanlar 10 üzerinden · yıldızlılar favorilerim · ✍️ olanlarda yorumum var</p>

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

        <ul class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($currentlyWatching as $series)
                <li class="flex gap-4 rounded-sm bg-paper-deep p-4 shadow-[0_8px_18px_-14px_rgb(60_40_20/0.5)] dark:shadow-[0_8px_18px_-10px_rgb(0_0_0/0.85)]">
                    <x-site.poster :title="$series['title']" :image-url="$series['posterUrl']" :colors="$series['posterColors']" :framed="false" class="w-14 self-start" />

                    <div class="min-w-0 flex-1">
                        <a href="{{ $series['url'] }}" class="block truncate font-display text-lg font-semibold hover:text-section-ink">{{ $series['title'] }}</a>
                        <p class="font-hand text-lg leading-tight text-ink-soft">{{ $series['status']->emoji() }} {{ $series['status']->label() }}</p>

                        <div class="mt-2 flex items-center gap-3">
                            <x-site.pencil-progress
                                :value="$series['episode']"
                                :max="$series['episodeCount']"
                                :label="$series['title'].' sezon ilerlemesi'"
                                class="flex-1"
                            />
                            <span class="shrink-0 font-mono text-xs text-ink-soft">S{{ $series['season'] }} · B{{ $series['episode'] }}</span>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

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

                            {{--
                                One card for every entry. A review only makes the card longer:
                                a "yorum" tag, the opening lines and a link to the full review.
                            --}}
                            <article class="relative grid grid-cols-[3.5rem_1fr] gap-x-4 gap-y-3 rounded-sm bg-paper-deep p-3 shadow-[0_8px_18px_-14px_rgb(60_40_20/0.5)] sm:grid-cols-[4.5rem_1fr] sm:p-4 dark:shadow-[0_8px_18px_-10px_rgb(0_0_0/0.85)]">
                                <a href="{{ $entry['url'] }}" aria-hidden="true" tabindex="-1" @class(['self-start', 'sm:row-span-2' => $entry['hasReview']])>
                                    <x-site.poster :title="$entry['title']" :image-url="$entry['posterUrl']" :colors="$entry['posterColors']" :framed="false" />
                                </a>

                                {{-- The mark sits in the card's corner, like a grade on an exam paper --}}
                                <div class="absolute -top-3 -right-2 flex origin-top-right items-center max-sm:scale-75">
                                    @if ($entry['isFavorite'])
                                        <x-site.favorite-star class="-mr-1 -mb-6 size-7" />
                                    @endif
                                    @if ($entry['rating'] !== null)
                                        <x-site.grade :value="$entry['rating']" class="rounded-full bg-paper-deep" />
                                    @endif
                                </div>

                                <div class="min-w-0 self-center pr-16 sm:pr-20">
                                    <p class="font-mono text-[11px] text-ink-faint uppercase">{{ $entry['type']->label() }} · {{ $entry['year'] }} · {{ $entry['creator'] }}</p>
                                    <h4 class="mt-0.5 font-display text-lg leading-tight font-semibold text-balance sm:text-xl">
                                        <a href="{{ $entry['url'] }}" class="hover:text-section-ink">{{ $entry['title'] }}</a>
                                    </h4>

                                    @if ($entry['hasReview'] || $entry['isRewatch'] || $entry['status'])
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @if ($entry['hasReview'])
                                                <span class="rounded-sm bg-section px-1.5 font-mono text-[11px] font-semibold text-section-on">✍️ yorum</span>
                                            @endif
                                            @if ($entry['isRewatch'])
                                                <span class="rounded-sm bg-section/15 px-1.5 font-mono text-[11px] text-section-ink">↻ tekrar</span>
                                            @endif
                                            @if ($entry['status'])
                                                <span class="rounded-sm bg-section/15 px-1.5 font-mono text-[11px] text-section-ink">{{ $entry['status']->emoji() }} {{ $entry['status']->label() }} · S{{ $entry['season'] }} B{{ $entry['episode'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @if ($entry['hasReview'])
                                    <div class="col-span-2 sm:col-span-1 sm:col-start-2">
                                        <p class="line-clamp-2 border-l-2 border-section pl-3 text-ink-soft">{{ $entry['reviewExcerpt'] }}</p>
                                        <a href="{{ $entry['url'] }}" class="mt-2 inline-block text-sm font-semibold text-section-ink underline decoration-section decoration-2 underline-offset-4">yorumumu oku →</a>
                                    </div>
                                @endif
                            </article>
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
