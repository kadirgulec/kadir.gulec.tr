@use('App\Enums\Section')

@php
    $coverTilts = [-3, 2, -1.5, 3, -2, 1.5];
@endphp

<x-layouts::site :section="Section::Shelf" title="Okuduklarım" description="Okuduğum kitaplar: puanlar, kısa yorumlar, altını çizdiklerim ve bir okuma günlüğü.">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ collect($diaryByYear)->flatten(1)->count() }} okuma · {{ count($toRead) }} kitap sırada</p>

    <h1 class="relative mt-3 inline-block font-display text-[2.6rem] font-extrabold tracking-tight sm:text-6xl">
        Okuduklarım
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
        <p class="font-hand text-2xl text-ink-soft">puanlar 10 üzerinden · yıldızlılar favorilerim · ✍️ olanlarda yorumum var</p>
        <x-site.share-button />
    </div>

    <x-site.shelf-tabs current="books" class="mt-8" />

    {{-- Cover strip --}}
    <section class="mt-14" aria-labelledby="son-okuduklarim">
        <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
            <h2 id="son-okuduklarim" class="font-display text-2xl font-semibold">Son okuduklarım</h2>

            {{-- This year in one line; with a reading goal it also says how far along it is --}}
            <p class="font-hand text-2xl text-ink-soft">
                {{ now()->year }}: {{ $finishedThisYear }} kitap
                @if ($readingGoal)
                    · <a href="{{ route('goals.show', $readingGoal['slug']) }}" class="text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none]">hedef {{ $finishedThisYear }} / {{ $readingGoal['target'] }}</a>
                @endif
            </p>
        </div>

        <ul class="-mx-2 mt-4 flex snap-x gap-7 overflow-x-auto px-2 pt-6 pb-8 sm:grid sm:grid-cols-3 sm:overflow-visible lg:grid-cols-6 lg:gap-6">
            @foreach ($recent as $index => $entry)
                <li class="relative w-32 shrink-0 snap-start sm:w-auto">
                    <a href="{{ $entry['url'] }}" class="group block" aria-label="{{ $entry['title'] }}">
                        <x-site.book-cover
                            :title="$entry['title']"
                            :author="$entry['author']"
                            :image-url="$entry['coverUrl']"
                            :colors="$entry['coverColors']"
                            :tilt="$coverTilts[$index % count($coverTilts)]"
                            :caption="$entry['readAt']->locale('tr')->translatedFormat('j M')"
                        />
                    </a>

                    @if ($entry['rating'] !== null)
                        <x-site.grade :value="$entry['rating']" size="sm" class="pointer-events-none absolute -right-3 bottom-12 rounded-full bg-paper/85" />
                    @endif

                    @if ($entry['isFavorite'])
                        <x-site.favorite-star class="pointer-events-none absolute -top-3 -left-3 size-8" />
                    @endif

                    @if ($entry['hasReview'])
                        <span class="pointer-events-none absolute top-[42%] -right-3 rotate-6 rounded-full bg-section px-2 py-0.5 font-hand text-base leading-tight font-bold text-section-on shadow-sm">✍️ yorum</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Currently reading --}}
    <section class="mt-6" aria-labelledby="su-an-okuyorum">
        <h2 id="su-an-okuyorum" class="font-display text-2xl font-semibold">Şu an okuyorum</h2>

        @if ($currentlyReading)
            <ul class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($currentlyReading as $book)
                    <x-site.reading-card :book="$book" />
                @endforeach
            </ul>
        @else
            <p class="mt-4 font-hand text-2xl text-section-ink">Şu an elimde kitap yok, sıradakini seçiyorum.</p>
        @endif
    </section>

    {{-- Reading list: the first six, the rest behind "hepsini göster" (a plain <details>, no script needed) --}}
    @if ($toRead !== [])
        @php
            [$toReadFirst, $toReadRest] = [array_slice($toRead, 0, 6), array_slice($toRead, 6)];
            $stripClasses = '-mx-2 flex snap-x gap-6 overflow-x-auto px-2 pt-4 pb-6 sm:flex-wrap sm:overflow-visible';
        @endphp

        <section class="mt-16" aria-labelledby="sirada">
            <h2 id="sirada" class="font-display text-2xl font-semibold">Sırada</h2>
            <p class="font-hand text-xl text-ink-faint">okuyacaklarım, sırasıyla</p>

            <ol class="mt-4 {{ $stripClasses }}">
                @foreach ($toReadFirst as $index => $entry)
                    <x-site.to-read-entry :entry="$entry" :tilt="$coverTilts[($index + 2) % count($coverTilts)]" />
                @endforeach
            </ol>

            @if ($toReadRest !== [])
                <details class="group">
                    <summary class="inline-block cursor-pointer list-none font-hand text-2xl text-section-ink underline decoration-section decoration-wavy underline-offset-6 [text-decoration-skip-ink:none] [&::-webkit-details-marker]:hidden">
                        <span class="group-open:hidden">hepsini göster ({{ count($toReadRest) }} tane daha)</span>
                        <span class="hidden group-open:inline">daha az göster</span>
                    </summary>

                    <ol start="7" class="mt-2 {{ $stripClasses }}">
                        @foreach ($toReadRest as $index => $entry)
                            <x-site.to-read-entry :entry="$entry" :tilt="$coverTilts[($index + 8) % count($coverTilts)]" />
                        @endforeach
                    </ol>
                </details>
            @endif
        </section>
    @endif

    {{-- Diary, grouped by year --}}
    <section class="mt-16" aria-labelledby="gunluk">
        <h2 id="gunluk" class="font-display text-2xl font-semibold">Günlük</h2>

        @foreach ($diaryByYear as $year => $entries)
            <div class="mt-8">
                <div class="flex items-baseline gap-3 border-b-2 border-dashed border-rule pb-2">
                    <h3 class="font-display text-xl font-semibold">{{ $year }}</h3>
                    <span class="font-mono text-xs text-ink-faint">{{ $entries->count() }} kayıt</span>
                </div>

                <ol class="mt-5 flex flex-col gap-5">
                    @foreach ($entries as $entry)
                        <li class="grid grid-cols-[2.25rem_1fr] gap-3 sm:grid-cols-[3.5rem_1fr] sm:gap-4">
                            <div class="pt-3 text-center">
                                <span class="block font-display text-2xl leading-none font-semibold">{{ $entry['readAt']->day }}</span>
                                <span class="font-mono text-[10px] text-ink-faint uppercase">{{ $entry['readAt']->locale('tr')->translatedFormat('M') }}</span>
                            </div>

                            <x-site.book-diary-card :entry="$entry" />
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
        @if (! $diaryByYear)
            <p class="mt-6 font-hand text-2xl text-section-ink">Günlük henüz boş, ilk sayfa çevriliyor.</p>
        @endif
    </section>
</x-layouts::site>
