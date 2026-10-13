@use('App\Enums\ReadingStatus')
@use('App\Enums\Section')
@use('App\Support\TurkishDate')

@php
    $facts = collect([$entry['format']->label(), $entry['language'], $entry['year']])->filter()->implode(' · ');
    $quoteTilts = [1.5, -1, 2, -1.5];

    $readingDates = function (array $reading): string {
        $started = $reading['startedOn']->locale('tr');

        if ($reading['finishedOn'] === null) {
            return TurkishDate::onDayMonth($started).' başladım';
        }

        $finished = $reading['finishedOn']->locale('tr');

        return $started->year === $finished->year
            ? $started->translatedFormat('j F').' – '.$finished->translatedFormat('j F Y')
            : $started->translatedFormat('j F Y').' – '.$finished->translatedFormat('j F Y');
    };
@endphp

<x-layouts::site :section="Section::Shelf" :title="$entry['title']" :accent="$entry['accent']" :draft="$entry['isDraft']" :description="$entry['metaDescription']">
    <a href="{{ route('books.index') }}" class="font-hand text-xl text-ink-soft hover:text-section-ink">← Okuduklarım</a>

    <div class="mt-8 grid items-start gap-12 md:grid-cols-[15rem_1fr] md:gap-14">
        {{-- Cover, taped onto the page, with the mark and the star scribbled on it; a ribbon while reading --}}
        <div @class(['relative mx-auto w-52 md:mx-0 md:w-full', 'mb-12' => $entry['status']->isInProgress()])>
            <x-site.book-cover
                :title="$entry['title']"
                :author="$entry['author']"
                :image-url="$entry['coverUrl']"
                :colors="$entry['coverColors']"
                :progress="$entry['status']->isInProgress() ? $entry['progress'] : null"
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

            <x-site.share-button class="mt-4 -ml-2" />

            <dl class="mt-8 grid grid-cols-[6.5rem_1fr] items-baseline gap-x-4 gap-y-3">
                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Yazar</dt>
                <dd class="font-hand text-2xl leading-tight">{{ $entry['author'] }}</dd>

                @if ($entry['translator'])
                    <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Çeviren</dt>
                    <dd class="font-hand text-2xl leading-tight">{{ $entry['translator'] }}</dd>
                @endif

                @if ($entry['publisher'])
                    <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Yayınevi</dt>
                    <dd class="font-hand text-2xl leading-tight">{{ $entry['publisher'] }}</dd>
                @endif

                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Ne zaman</dt>
                <dd class="font-hand text-2xl leading-tight">
                    {{ $readingDates($entry['readings'][0]) }}
                    @if ($entry['isReread'])
                        <span class="text-section-ink">(tekrar okudum)</span>
                    @endif
                </dd>

                <dt class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">Durum</dt>
                <dd class="font-hand text-2xl leading-tight">
                    {{ $entry['status']->emoji() }} {{ $entry['status']->label() }}
                    @if ($entry['status'] !== ReadingStatus::Finished && $entry['progress'] !== null)
                        <span class="font-mono text-sm text-ink-soft">· %{{ $entry['progress'] }}</span>
                    @endif
                </dd>
            </dl>

            @if ($entry['isFavorite'])
                <p class="mt-7 font-hand text-2xl text-pen-red">favorilerime girdi! ★</p>
            @endif

            @unless (app()->isProduction())
                <p class="mt-7 inline-block rotate-[-0.5deg] border border-dashed border-ink/30 px-3 py-1.5 font-hand text-lg text-ink-faint">
                    ✎ prototip: örnek veri, takip düğmesi Adım 7'de gelecek
                </p>
            @endunless
        </div>
    </div>

    @if ($entry['hasReview'])
        {{-- Review on lined paper --}}
        <section class="mt-20 max-w-2xl" aria-labelledby="yorumum">
            <h2 id="yorumum" class="relative inline-block font-display text-3xl font-semibold">
                Yorumum
                <x-site.scribble variant="double" class="absolute -bottom-3 left-0 h-3 w-full text-section" />
            </h2>

            <div class="prose-notebook prose-review mt-10">{{ $entry['reviewHtml'] }}</div>

            <div class="mt-12 flex items-center gap-4">
                <x-site.logo class="stamp size-16 -rotate-12 text-section-ink" />
                <p class="font-hand text-xl leading-tight text-ink-soft">
                    Kadir<br>
                    {{ $entry['readAt']->locale('tr')->translatedFormat('j F Y') }}
                </p>
            </div>
        </section>
    @else
        <section class="mt-20 max-w-2xl" aria-labelledby="ozet">
            <h2 id="ozet" class="font-display text-2xl font-semibold">Özet</h2>
            @if ($entry['overview'])
                <p class="mt-4 text-lg leading-relaxed text-ink-soft">{{ $entry['overview'] }}</p>
            @endif

            <p class="mt-8 font-hand text-2xl text-section-ink">Bu kitap hakkında henüz bir şey yazmadım.</p>
        </section>
    @endif

    {{-- Collected quotes: kept apart from the review, so a book without one can have them too --}}
    @if ($entry['quotes'])
        <section class="mt-20" aria-labelledby="alintilar">
            <h2 id="alintilar" class="font-display text-2xl font-semibold">Altını çizdiklerim</h2>

            <ul class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($entry['quotes'] as $index => $quote)
                    <li>
                        <x-site.quote-note :quote="$quote" :tilt="$quoteTilts[$index % count($quoteTilts)]" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Notes on the board that came out of this book --}}
    @if ($notes)
        <section class="mt-20" aria-labelledby="notlarim">
            <h2 id="notlarim" class="font-display text-2xl font-semibold">Bu kitaptan notlarım</h2>

            <ul class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($notes as $note)
                    <li class="pt-3">
                        <x-site.post-it :note="$note" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Every reading of the book, when there was more than one --}}
    @if (count($entry['readings']) > 1)
        <section class="mt-16 max-w-2xl" aria-labelledby="okumalar">
            <h2 id="okumalar" class="font-display text-2xl font-semibold">Okumalarım</h2>

            <ol class="mt-5 flex flex-col gap-3">
                @foreach ($entry['readings'] as $reading)
                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 border-b border-dotted border-rule pb-2">
                        <span class="font-hand text-2xl leading-tight">{{ $readingDates($reading) }}</span>
                        <span class="font-mono text-xs text-ink-soft">{{ $reading['status']->emoji() }} {{ $reading['status']->label() }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</x-layouts::site>
