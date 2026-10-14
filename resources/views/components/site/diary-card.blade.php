@props([
    'url',
    'title',
    'kicker',
    'rating' => null,
    'isFavorite' => false,
    'badges' => [],
    'excerpt' => null,
])

{{--
    One diary entry on the shelf: a film, a series or a book, with its cover in
    the "cover" slot. Every entry is the same card; a review only makes it longer:
    the opening lines and a link to the full review. A badge is ['label' => …,
    'strong' => true] for the filled "yorum" tag, otherwise a pale one.
--}}
<article {{ $attributes->class('relative grid grid-cols-[3.5rem_1fr] gap-x-4 gap-y-3 rounded-sm bg-paper-deep p-3 shadow-[0_8px_18px_-14px_rgb(60_40_20/0.5)] sm:grid-cols-[4.5rem_1fr] sm:p-4 dark:shadow-[0_8px_18px_-10px_rgb(0_0_0/0.85)]') }}>
    <a href="{{ $url }}" aria-hidden="true" tabindex="-1" @class(['self-start', 'sm:row-span-2' => $excerpt !== null])>
        {{ $cover }}
    </a>

    {{-- The mark sits in the card's corner, like a grade on an exam paper --}}
    <div class="absolute -top-3 -right-2 flex origin-top-right items-center max-sm:scale-75">
        @if ($isFavorite)
            <x-site.favorite-star class="-mr-1 -mb-6 size-7" />
        @endif
        @if ($rating !== null)
            <x-site.grade :value="$rating" class="rounded-full bg-paper-deep" />
        @endif
    </div>

    <div class="min-w-0 self-center pr-16 sm:pr-20">
        <p class="font-mono text-[11px] text-ink-faint uppercase">{{ $kicker }}</p>
        <h4 class="mt-0.5 font-display text-lg leading-tight font-semibold text-balance sm:text-xl">
            <a href="{{ $url }}" class="hover:text-section-ink">{{ $title }}</a>
        </h4>

        @if ($badges)
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($badges as $badge)
                    <span @class([
                        'rounded-sm px-1.5 font-mono text-[11px]',
                        'bg-section font-semibold text-section-on' => $badge['strong'] ?? false,
                        'bg-section/15 text-section-ink' => ! ($badge['strong'] ?? false),
                    ])>{{ $badge['label'] }}</span>
                @endforeach
            </div>
        @endif
    </div>

    @if ($excerpt !== null)
        <div class="col-span-2 sm:col-span-1 sm:col-start-2">
            <p class="line-clamp-2 border-l-2 border-section pl-3 text-ink-soft">{{ $excerpt }}</p>
            <a href="{{ $url }}" class="mt-2 inline-block text-sm font-semibold text-section-ink underline decoration-section decoration-2 underline-offset-4">yorumumu oku →</a>
        </div>
    @endif
</article>
