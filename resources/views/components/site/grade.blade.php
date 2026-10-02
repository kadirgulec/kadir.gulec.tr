@props(['value', 'size' => 'md'])

{{-- "Teacher's mark": the rating out of 10, circled in red pen. Turkish decimal comma (8,5). --}}
@php
    $sizeClasses = match ($size) {
        'sm' => 'size-10 text-xl',
        'lg' => 'size-20 text-[2.5rem]',
        default => 'size-14 text-[1.7rem]',
    };

    // The circle needs a positioned parent; callers that pin the mark use "absolute" instead.
    $position = str_contains($attributes->get('class', ''), 'absolute') ? '' : 'relative';
@endphp

<span {{ $attributes->merge(['class' => "{$position} inline-flex shrink-0 -rotate-6 items-center justify-center font-hand leading-none font-bold text-pen-red {$sizeClasses}"]) }}>
    <x-site.scribble variant="circle" class="absolute -inset-1 size-[calc(100%+0.5rem)]" />
    <span class="sr-only">Puan:</span>
    {{ \Illuminate\Support\Number::format($value, maxPrecision: 1, locale: 'tr') }}
    <span class="sr-only">/ 10</span>
</span>
