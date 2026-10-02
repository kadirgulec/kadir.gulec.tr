@props([
    'section' => null,
    'tilt' => 0,
    'tape' => 'center',
    'label' => null,
    'moreHref' => null,
    'moreLabel' => null,
])

{{-- A note card taped onto the page. Straightens up on hover. --}}
<article
    @if ($section) data-section="{{ $section->value }}" @endif
    style="--tilt: {{ $tilt }}deg"
    {{ $attributes->merge(['class' => 'relative rotate-(--tilt) rounded-sm bg-paper-deep p-5 pt-7 shadow-[0_10px_24px_-14px_rgb(60_40_20/0.45)] transition duration-200 ease-out hover:-translate-y-0.5 hover:rotate-0 motion-reduce:transition-none sm:p-6 sm:pt-8 dark:shadow-[0_10px_24px_-10px_rgb(0_0_0/0.85)]']) }}
>
    @switch($tape)
        @case('center')
            <span class="tape -top-3 left-1/2 -translate-x-1/2 -rotate-2"></span>
            @break
        @case('left')
            <span class="tape -top-3 -left-4 -rotate-12"></span>
            @break
        @case('right')
            <span class="tape -top-3 -right-4 rotate-12"></span>
            @break
    @endswitch

    @if ($label || $moreHref)
        <header class="mb-4 flex items-baseline justify-between gap-4">
            @if ($label)
                <p class="font-hand text-2xl leading-none font-bold text-section-ink">{{ $label }}</p>
            @endif

            @if ($moreHref)
                <a href="{{ $moreHref }}" class="shrink-0 text-sm font-semibold text-ink-soft underline decoration-section decoration-2 underline-offset-4 hover:text-ink">{{ $moreLabel }}</a>
            @endif
        </header>
    @endif

    {{ $slot }}
</article>
