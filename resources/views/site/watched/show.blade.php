@use('App\Enums\Section')
@use('App\Enums\WatchableType')
@use('App\Support\TurkishDate')

@php
    $isFilm = $entry['type'] === WatchableType::Film;

    $runtime = $entry['runtimeMinutes']
        ? collect([
            intdiv($entry['runtimeMinutes'], 60) ? intdiv($entry['runtimeMinutes'], 60).' sa' : null,
            $entry['runtimeMinutes'] % 60 ? ($entry['runtimeMinutes'] % 60).' dk' : null,
        ])->filter()->implode(' ')
        : null;

    $facts = collect([$entry['type']->label(), $entry['year'], $runtime])->filter()->implode(' · ');
@endphp

<x-layouts::site :section="Section::Watched" :title="$entry['title']" :accent="$entry['accent']">
    <a href="{{ route('watched.index') }}" class="font-hand text-xl text-ink-soft hover:text-section-ink">← İzlediklerim</a>

    <div class="mt-8 grid items-start gap-12 md:grid-cols-[15rem_1fr] md:gap-14">
        {{-- Poster, taped onto the page, with the mark and the star scribbled on it --}}
        <div class="relative mx-auto w-52 md:mx-0 md:w-full">
            <x-site.poster
                :title="$entry['title']"
                :year="$entry['year']"
                :image-url="$entry['posterUrl']"
                :colors="$entry['posterColors']"
                tilt="-3"
            />

            @if ($entry['rating'] !== null)
                <x-site.grade :value="$entry['rating']" size="lg" class="absolute -right-6 -bottom-7 rounded-full bg-paper/85" />
            @endif

            @if ($entry['isFavorite'])
                <x-site.favorite-star class="absolute -top-6 -right-5 size-14" />
            @endif
        </div>

        <div>
            <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ $facts }}</p>

            <h1 class="relative mt-3 inline-block font-display text-4xl leading-[1.05] font-extrabold tracking-tight text-balance sm:text-5xl">
                {{ $entry['title'] }}
                <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
            </h1>

            @if ($entry['originalTitle'])
                <p class="mt-5 font-display text-lg text-ink-soft italic">{{ $entry['originalTitle'] }}</p>
            @endif

            <dl class="mt-8 grid grid-cols-[6.5rem_1fr] items-baseline gap-x-4 gap-y-3">
                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">{{ $isFilm ? 'Yönetmen' : 'Yaratıcı' }}</dt>
                <dd class="font-hand text-2xl leading-tight">{{ $entry['creator'] }}</dd>

                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Ne zaman</dt>
                <dd class="font-hand text-2xl leading-tight">
                    {{ TurkishDate::onDayMonth($entry['watchedAt']) }}
                    @if ($entry['isRewatch'])
                        <span class="text-section-ink">(tekrar izledim)</span>
                    @endif
                </dd>

                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Nerede</dt>
                <dd class="font-hand text-2xl leading-tight">{{ $entry['place'] }}</dd>

                @if ($entry['status'])
                    <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Durum</dt>
                    <dd class="font-hand text-2xl leading-tight">
                        {{ $entry['status']->emoji() }} {{ $entry['status']->label() }}
                        <span class="font-mono text-sm text-ink-soft">· S{{ $entry['season'] }} B{{ $entry['episode'] }}</span>
                    </dd>
                @endif
            </dl>

            @if ($entry['genres'])
                <ul class="mt-7 flex flex-wrap gap-2" aria-label="Türler">
                    @foreach ($entry['genres'] as $genre)
                        <li class="rotate-[-1.5deg] bg-section/20 px-2 py-0.5 font-mono text-xs text-section-ink odd:rotate-[1.5deg]">{{ $genre }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($entry['isFavorite'])
                <p class="mt-7 font-hand text-2xl text-pen-red">favorilerime girdi! ★</p>
            @endif
        </div>
    </div>

    @if ($entry['hasReview'])
        {{-- Review on lined paper --}}
        <section class="mt-20 max-w-2xl" aria-labelledby="yorumum">
            <h2 id="yorumum" class="relative inline-block font-display text-3xl font-semibold">
                Yorumum
                <x-site.scribble variant="double" class="absolute -bottom-3 left-0 h-3 w-full text-section" />
            </h2>

            <div class="mt-10 flex flex-col text-lg">
                @foreach ($entry['review'] as $block)
                    @switch($block['type'])
                        @case('spoiler')
                            <x-site.spoiler class="my-8">{{ $block['text'] }}</x-site.spoiler>
                            @break
                        @case('quote')
                            <x-site.sticky-note :by="$block['by'] ?? null" class="mx-auto my-10">{{ $block['text'] }}</x-site.sticky-note>
                            @break
                        @default
                            <p class="ruled pt-8 first:pt-0">{{ $block['text'] }}</p>
                    @endswitch
                @endforeach
            </div>

            <div class="mt-12 flex items-center gap-4">
                <x-site.logo class="stamp size-16 -rotate-12 text-section-ink" />
                <p class="font-hand text-xl leading-tight text-ink-soft">
                    Kadir<br>
                    {{ $entry['watchedAt']->locale('tr')->translatedFormat('j F Y') }}
                </p>
            </div>
        </section>
    @else
        <section class="mt-20 max-w-2xl" aria-labelledby="ozet">
            <h2 id="ozet" class="font-display text-2xl font-semibold">Özet</h2>
            <p class="mt-4 text-lg leading-relaxed text-ink-soft">{{ $entry['overview'] }}</p>

            <p class="mt-8 font-hand text-2xl text-section-ink">Bu {{ $isFilm ? 'film' : 'dizi' }} hakkında henüz bir şey yazmadım.</p>
        </section>
    @endif

    {{-- Seasons: progress per season, optional mark and note --}}
    @if ($entry['seasons'])
        <section class="mt-16 max-w-2xl" aria-labelledby="sezonlar">
            <h2 id="sezonlar" class="font-display text-2xl font-semibold">Sezonlar</h2>

            <ol class="mt-5 flex flex-col gap-4">
                @foreach ($entry['seasons'] as $season)
                    @php
                        $isCurrentSeason = $season['number'] === $entry['season'];
                        $watchedEpisodes = match (true) {
                            $season['number'] < $entry['season'] => $season['episodeCount'],
                            $isCurrentSeason => $entry['episode'],
                            default => 0,
                        };
                        $isComplete = $watchedEpisodes === $season['episodeCount'];
                    @endphp

                    <li class="relative rounded-sm bg-paper-deep p-4 pr-20">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h3 class="font-display text-lg font-semibold">{{ $season['number'] }}. sezon</h3>
                            <span class="font-mono text-xs text-ink-faint">{{ $season['episodeCount'] }} bölüm</span>
                            @if ($isComplete)
                                <span class="font-hand text-lg text-section-ink">bitti ✓</span>
                            @elseif ($isCurrentSeason)
                                <span class="font-hand text-lg text-section-ink">{{ $entry['status']->emoji() }} {{ $entry['status']->label() }} · B{{ $entry['episode'] }}</span>
                            @endif
                        </div>

                        <x-site.pencil-progress :value="$watchedEpisodes" :max="$season['episodeCount']" :label="$season['number'].'. sezon ilerlemesi'" class="mt-3" />

                        @if ($season['note'])
                            <p class="mt-3 font-hand text-xl leading-snug text-ink-soft">{{ $season['note'] }}</p>
                        @endif

                        @if ($season['rating'] !== null)
                            <x-site.grade :value="$season['rating']" size="sm" class="absolute top-3 right-4" />
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($entry['cast'])
        <section class="mt-16 max-w-2xl" aria-labelledby="oyuncular">
            <h2 id="oyuncular" class="font-display text-2xl font-semibold">Oyuncular</h2>

            <ul class="mt-4 grid gap-x-8 gap-y-2 sm:grid-cols-2">
                @foreach ($entry['cast'] as $member)
                    <li class="flex items-baseline justify-between gap-3 border-b border-dotted border-rule pb-1.5">
                        <span class="font-semibold">{{ $member['name'] }}</span>
                        <span class="truncate font-mono text-xs text-ink-faint">{{ $member['role'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <p class="mt-16 font-mono text-[11px] text-ink-faint">Yapım bilgileri ve afiş: The Movie Database (TMDB)</p>
</x-layouts::site>
