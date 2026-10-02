@props(['variant' => 'underline'])

{{-- Hand-drawn strokes that draw themselves once they scroll into view. Color: currentColor. --}}
<svg
    viewBox="{{ $variant === 'circle' ? '0 0 120 60' : '0 0 200 14' }}"
    preserveAspectRatio="none"
    fill="none"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    {{ $attributes->merge(['class' => 'draw pointer-events-none overflow-visible']) }}
>
    @switch($variant)
        @case('double')
            <path pathLength="1" stroke-width="3" d="M3 6c38-4 79 1 120-1.5 26-1.5 50-.6 74 .8" />
            <path pathLength="1" stroke-width="2.4" d="M18 11.5c46-3 95-1.6 160-.8" />
            @break
        @case('circle')
            <path pathLength="1" stroke-width="2.6" d="M70 6C38 2 6 12 5 30c-1 17 30 26 60 25 30-1 52-12 50-28C113 10 86 4 60 7c-12 1.4-22 4-29 8" />
            @break
        @default
            <path pathLength="1" stroke-width="3.2" d="M3 9c22-5 46-4 70-2.4 30 2 58-3.6 86-3.4 14 .1 26 1.4 38 3.4" />
    @endswitch
</svg>
