@props([
    'title',
    'year' => null,
    'imageUrl' => null,
    'colors' => ['#d6cbb8', '#4a3f35'],
    'caption' => null,
    'tilt' => -2,
    'framed' => true,
])

{{--
    Film/series poster. Without an image (or before one is fetched) it renders a
    generated poster from two colors, so a card never shows an empty box.
--}}
@php
    $artwork = $imageUrl
        ? null
        : 'background-image: linear-gradient(160deg, '.$colors[0].' 0%, '.$colors[1].' 100%)';
@endphp

@if ($framed)
    <figure
        style="--tilt: {{ $tilt }}deg"
        {{ $attributes->merge(['class' => 'relative rotate-(--tilt) bg-[#fffdf7] p-2 pb-2.5 shadow-[0_6px_16px_-8px_rgb(60_40_20/0.5)] transition duration-200 ease-out hover:-translate-y-1 hover:rotate-0 motion-reduce:transition-none dark:bg-[#2e2a25] dark:shadow-[0_6px_16px_-6px_rgb(0_0_0/0.9)]']) }}
    >
        <span class="tape -top-2.5 left-1/2 h-5 w-14 -translate-x-1/2 rotate-3"></span>
@else
    <div {{ $attributes->merge(['class' => 'relative shrink-0 overflow-hidden rounded-[3px] shadow-sm']) }}>
@endif

    <div class="relative aspect-[2/3] w-full overflow-hidden" @if ($artwork) style="{{ $artwork }}" @endif>
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $title }} afişi" class="size-full object-cover" loading="lazy" />
        @else
            <div class="paper absolute inset-0 opacity-25 mix-blend-overlay" aria-hidden="true"></div>
            @if ($framed)
                <div class="absolute inset-x-0 bottom-0 flex flex-col gap-0.5 p-2.5 text-[#fffdf7]">
                    @if ($year)
                        <span class="font-mono text-[9px] tracking-widest opacity-80">{{ $year }}</span>
                    @endif
                    <span class="font-display text-base leading-tight font-extrabold text-balance drop-shadow-sm">{{ $title }}</span>
                </div>
            @endif
            <span class="sr-only">{{ $title }} afişi</span>
        @endif
    </div>

@if ($framed)
        @if ($caption)
            <figcaption class="mt-1.5 text-center font-hand text-lg leading-tight text-ink-soft">{{ $caption }}</figcaption>
        @endif
    </figure>
@else
    </div>
@endif
