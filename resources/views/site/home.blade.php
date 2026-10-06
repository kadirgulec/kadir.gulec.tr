@use('App\Enums\Section')

@php
    $today = now()->locale('tr');
@endphp

<x-layouts::site :section="Section::Home">
    {{-- Greeting --}}
    <section class="grid items-start gap-8 md:grid-cols-[1fr_auto]">
        <div>
            <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">Düren · {{ $today->translatedFormat('j F Y') }}</p>

            <h1 class="mt-4 max-w-2xl font-display text-4xl leading-[1.08] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-[3.4rem]">
                Yedi yıl hırsız kovaladım, şimdi hırsız beni
                <span class="relative whitespace-nowrap">
                    kovalıyor.
                    <x-site.scribble class="absolute -bottom-2.5 left-0 h-3.5 w-full text-section" />
                </span>
            </h1>

            <p class="mt-7 font-hand text-2xl text-ink-soft sm:text-[1.7rem]">
                Bu benim karalama defterim. Sayfaları karıştır
                <span class="inline-block lg:hidden" aria-hidden="true">↓</span>
                <span class="hidden lg:inline-block" aria-hidden="true">→</span>
            </p>
        </div>

        <x-site.logo class="stamp mt-6 mr-2 hidden size-32 -rotate-12 text-home-ink md:block" />
    </section>

    {{-- "Lately" board --}}
    <section class="mt-16 sm:mt-20" aria-labelledby="su-siralar">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b-2 border-dashed border-rule pb-2">
            <h2 id="su-siralar" class="font-display text-3xl font-semibold">Şu sıralar</h2>
            <p class="font-mono text-xs text-ink-faint">son güncelleme · {{ $today->translatedFormat('j F') }}</p>
        </div>

        <div class="mt-10">
            {{-- Two free columns on wide screens: the cards stack without lining up in rows, in the same order as on a phone. --}}
            <div class="gap-x-8 md:columns-2">
                {{-- Last watched film --}}
                @if ($lastWatched)
                <x-site.note :section="Section::Watched" tilt="-1.2" tape="left" label="son izlediğim" :more-href="route('watched.index')" more-label="tümü →" class="mb-12 inline-block w-full break-inside-avoid">
                    <div class="flex gap-5">
                        <x-site.poster
                            :title="$lastWatched['title']"
                            :year="$lastWatched['year']"
                            :image-url="$lastWatched['posterUrl']"
                            :colors="$lastWatched['posterColors']"
                            tilt="-3"
                            class="w-28 shrink-0 self-start sm:w-32"
                        />

                        <div class="flex min-w-0 flex-col">
                            <h3 class="font-display text-xl leading-tight font-semibold text-balance">
                                <a href="{{ $lastWatched['url'] }}" class="hover:text-section-ink">{{ $lastWatched['title'] }}</a>
                            </h3>
                            <p class="mt-1 font-mono text-xs text-ink-faint">{{ $lastWatched['year'] }} · {{ $lastWatched['creator'] }}</p>
                            <p class="mt-2 font-hand text-xl text-ink-soft">{{ \App\Support\TurkishDate::onDayMonth($lastWatched['watchedAt']) }} izledim</p>

                            <div class="mt-3 flex items-center gap-2">
                                @if ($lastWatched['rating'] !== null)
                                    <x-site.grade :value="$lastWatched['rating']" />
                                @endif
                                @if ($lastWatched['isFavorite'])
                                    <x-site.favorite-star class="-mt-6" />
                                @endif
                            </div>

                            @if ($lastWatched['hasReview'])
                                <a href="{{ $lastWatched['url'] }}" class="mt-auto pt-3 text-sm font-semibold text-section-ink underline decoration-section decoration-2 underline-offset-4">
                                    yorumumu oku →
                                </a>
                            @endif
                        </div>
                    </div>
                </x-site.note>
                @endif

                {{-- Latest post --}}
                @if ($latestPost)
                <x-site.note :section="Section::Posts" tilt="0.8" label="son yazdığım" :more-href="route('posts.index')" more-label="tümü →" class="mb-12 inline-block w-full break-inside-avoid">
                    <p class="font-mono text-xs text-ink-faint">
                        {{ $latestPost['publishedAt']->locale('tr')->translatedFormat('j F Y') }} · {{ $latestPost['readingMinutes'] }} dk okuma
                    </p>

                    <h3 class="mt-2 font-display text-2xl leading-tight font-semibold text-balance">
                        <a href="{{ $latestPost['url'] }}" class="hover:text-section-ink">{{ $latestPost['title'] }}</a>
                    </h3>

                    <p class="mt-3 line-clamp-3 text-ink-soft">{{ $latestPost['excerpt'] }}</p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @foreach ($latestPost['tags'] as $index => $tag)
                            <x-site.tag :name="$tag" :slug="$latestPost['tagSlugs'][$index]" />
                        @endforeach
                    </div>
                </x-site.note>
                @endif

                {{-- Latest note --}}
                @if ($latestNote)
                <x-site.note :section="Section::Notes" tilt="-0.6" tape="left" label="son öğrendiğim" :more-href="route('notes.index')" more-label="tümü →" class="mb-12 inline-block w-full break-inside-avoid">
                    <x-site.post-it :note="$latestNote" class="mx-auto mt-6 max-w-sm" />
                </x-site.note>
                @endif

                {{-- Chains --}}
                @if ($chains)
                <x-site.note :section="Section::Goals" tilt="1" tape="right" label="zincirler" :more-href="route('goals.index')" more-label="tüm hedefler →" class="mb-12 inline-block w-full break-inside-avoid">
                    <ul class="flex flex-col gap-5">
                        @foreach ($chains as $chain)
                            <li>
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="font-semibold">
                                        @if ($chain['title'])
                                            {{ $chain['title'] }}
                                        @else
                                            <span aria-hidden="true">🔒</span>
                                            <x-site.censored :length="$chain['titleLength']" label="sansürlü zincir" />
                                        @endif
                                    </span>
                                    <span class="shrink-0 font-mono text-sm font-semibold text-section-ink">🔥 {{ $chain['streak'] }} {{ $chain['period']->unit() }}</span>
                                </div>
                                <x-site.chain :days="$chain['days']" :unit="$chain['period']->unit()" class="mt-2" />
                            </li>
                        @endforeach
                    </ul>

                    <p class="mt-5 flex items-center gap-2 font-hand text-lg text-ink-faint">
                        <span class="inline-block h-3 w-1.5 rotate-[28deg] bg-ink-faint/55" aria-hidden="true"></span>
                        bantlı halka = mazeretli
                    </p>
                </x-site.note>
                @endif

                {{-- Currently watching --}}
                @if ($currentlyWatching)
                <x-site.note :section="Section::Watched" tilt="-0.8" label="şu an izliyorum" :more-href="route('watched.index')" more-label="tümü →" class="mb-12 inline-block w-full break-inside-avoid">
                    <ul class="flex flex-col gap-5">
                        @foreach ($currentlyWatching as $series)
                            <li class="flex items-center gap-4">
                                <x-site.poster :title="$series['title']" :image-url="$series['posterUrl']" :colors="$series['posterColors']" :framed="false" class="w-12" />

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <a href="{{ $series['url'] }}" class="truncate font-display text-lg font-semibold hover:text-section-ink">{{ $series['title'] }}</a>
                                        <span class="shrink-0 font-mono text-xs text-ink-soft">S{{ $series['season'] }} · B{{ $series['episode'] }}</span>
                                    </div>
                                    <x-site.pencil-progress
                                        :value="$series['episode']"
                                        :max="$series['episodeCount']"
                                        :label="$series['title'].' sezon ilerlemesi'"
                                        class="mt-2"
                                    />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-site.note>
                @endif
            </div>

            {{-- Featured project --}}
            @if ($featuredProject)
            <x-site.note :section="Section::Projects" tilt="-0.4" label="üzerinde çalıştığım" :more-href="route('projects.index')" more-label="tüm projeler →">
                <div class="grid items-start gap-6 md:grid-cols-[1.15fr_1fr] md:gap-8">
                    <a href="{{ $featuredProject['url'] }}" class="block rotate-[-1deg] transition duration-200 hover:rotate-0 motion-reduce:transition-none">
                        <x-site.project-shot :project="$featuredProject" />
                    </a>

                    <div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <h3 class="font-display text-3xl font-extrabold">
                                <a href="{{ $featuredProject['url'] }}" class="hover:text-section-ink">{{ $featuredProject['name'] }}</a>
                            </h3>
                            <x-site.status-stamp :status="$featuredProject['status']" />
                        </div>

                        <p class="mt-3 text-ink-soft">{{ $featuredProject['tagline'] }}</p>

                        <ul class="mt-4 flex flex-wrap gap-2" aria-label="Kullanılan teknolojiler">
                            @foreach ($featuredProject['stack'] as $technology)
                                <li class="rounded-sm border border-section/60 px-2 py-0.5 font-mono text-xs text-section-ink">{{ $technology }}</li>
                            @endforeach
                        </ul>

                        @if ($featuredProject['latestLog'])
                            <p class="mt-5 border-l-2 border-section pl-3 text-sm">
                                <span class="font-mono text-xs text-ink-faint">devlog · {{ $featuredProject['latestLog']['date']->locale('tr')->translatedFormat('j F') }}</span><br>
                                {{ $featuredProject['latestLog']['text'] }}
                            </p>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                            <a href="{{ $featuredProject['url'] }}" class="text-section-ink underline decoration-section decoration-2 underline-offset-4">projeyi incele →</a>
                            @if ($featuredProject['demoUrl'])
                                <a href="{{ $featuredProject['demoUrl'] }}" class="text-ink-soft underline decoration-dotted underline-offset-4 hover:text-ink">demo ↗</a>
                            @endif
                        </div>
                    </div>
                </div>
            </x-site.note>
            @endif
        </div>
    </section>
</x-layouts::site>
