@props([
    'title',
    'author' => null,
    'imageUrl' => null,
    'colors' => ['#d6cbb8', '#4a3f35'],
    'caption' => null,
    'tilt' => -2,
    'framed' => true,
    'progress' => null,
])

{{--
    Book cover. "framed" tapes it onto the page (no polaroid: a book is an object
    of its own); otherwise it is a small cover for cards. Without an image it
    draws a cover from two colors with the title and the author; the title is
    sized to the cover's width (container query units) and to its longest word,
    so "Tutunamayanlar" still fits a small cover in one piece. "progress"
    (0–100) hangs a bookmark ribbon over the cover for a book being read.
--}}
@php
    $artwork = $imageUrl
        ? null
        : 'background-image: linear-gradient(160deg, '.$colors[0].' 0%, '.$colors[1].' 100%)';

    // A word never breaks mid-way: the longest one has to fit the ~80cqw text box, at ~0.68em a letter.
    $longestWord = collect(preg_split('/\s+/u', $title))->max(fn (string $word): int => mb_strlen($word));
    $titleSize = min(10, round(80 / max(1, $longestWord * 0.68), 2));
@endphp

<figure
    @if ($framed) style="--tilt: {{ $tilt }}deg" @endif
    {{ $attributes->class([
        'relative shrink-0',
        'rotate-(--tilt) transition duration-200 ease-out hover:-translate-y-1 hover:rotate-0 motion-reduce:transition-none' => $framed,
    ]) }}
>
    @if ($framed)
        <span class="tape -top-2.5 left-1/2 z-10 h-5 w-14 -translate-x-1/2 rotate-3"></span>
    @endif

    <div class="relative">
        <div
            @class([
                '@container relative aspect-[2/3] w-full overflow-hidden rounded-r-[4px] rounded-l-[2px]',
                'shadow-[0_8px_18px_-8px_rgb(60_40_20/0.6)] dark:shadow-[0_8px_18px_-6px_rgb(0_0_0/0.9)]' => $framed,
                'shadow-sm' => ! $framed,
            ])
            @if ($artwork) style="{{ $artwork }}" @endif
        >
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $title }} kapağı" class="size-full object-cover" loading="lazy" />
            @else
                <div class="paper absolute inset-0 opacity-25 mix-blend-overlay" aria-hidden="true"></div>
                @if ($framed)
                    <div class="absolute inset-0 flex flex-col justify-between pt-[9cqw] pr-[6cqw] pb-[7cqw] pl-[11cqw] text-[#fffdf7]">
                        <span style="--title-size: {{ $titleSize }}cqw" class="font-display text-[clamp(0.5rem,var(--title-size),1.6rem)] leading-tight font-extrabold text-balance wrap-break-word drop-shadow-sm">{{ $title }}</span>
                        @if ($author)
                            <span class="font-mono text-[9px] tracking-widest uppercase opacity-85">{{ $author }}</span>
                        @endif
                    </div>
                @endif
                <span class="sr-only">{{ $title }} kapağı</span>
            @endif

            {{-- The spine: a darker strip and a crease along the left edge --}}
            <span class="absolute inset-y-0 left-0 w-[7%] bg-black/25" aria-hidden="true"></span>
            <span class="absolute inset-y-0 left-[7%] w-px bg-white/20" aria-hidden="true"></span>
        </div>

        @if ($progress !== null)
            {{-- Bookmark ribbon: tucked in at the top, hanging out below, the percentage written at its end --}}
            <span class="pointer-events-none absolute -top-1 right-[18%] -bottom-9 w-3.5 bg-section shadow-[1px_2px_3px_rgb(0_0_0/0.2)] [clip-path:polygon(0_0,100%_0,100%_100%,50%_88%,0_100%)]" aria-hidden="true"></span>
            <span class="absolute top-[calc(100%+2.4rem)] right-[18%] translate-x-1/3 font-hand text-xl leading-none font-bold whitespace-nowrap text-section-ink">%{{ $progress }}</span>
        @endif
    </div>

    @if ($caption)
        <figcaption class="mt-2 text-center font-hand text-lg leading-tight text-ink-soft">{{ $caption }}</figcaption>
    @endif
</figure>
