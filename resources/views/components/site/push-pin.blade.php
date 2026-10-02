@props(['color' => 'var(--color-pen-red)'])

{{-- A push pin for the cork board. --}}
<svg viewBox="0 0 28 28" aria-hidden="true" {{ $attributes->merge(['class' => 'size-7 drop-shadow-[1px_3px_2px_rgb(0_0_0/0.35)]']) }}>
    <circle cx="14" cy="12" r="8.5" fill="{{ $color }}" stroke="rgb(0 0 0 / 0.25)" stroke-width="1" />
    <circle cx="15.5" cy="13.5" r="5" fill="rgb(0 0 0 / 0.12)" />
    <ellipse cx="11" cy="9" rx="2.6" ry="1.8" fill="white" opacity="0.6" />
</svg>
