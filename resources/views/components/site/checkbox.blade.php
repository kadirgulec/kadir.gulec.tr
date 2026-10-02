@props(['checked' => false])

{{-- A hand-drawn box; the tick draws itself in red pen when the item is done. --}}
<svg viewBox="0 0 32 32" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->merge(['class' => 'size-6 shrink-0 overflow-visible']) }}>
    <path d="M5.5 6.2c6.8-.9 14-.8 21 .2.6 6.6.5 13.3-.2 19.8-7 .7-14 .6-20.7-.2-.6-6.7-.6-13.2-.1-19.8Z" stroke="currentColor" stroke-width="1.8" />
    @if ($checked)
        <g class="draw">
            <path pathLength="1" d="M8.5 16.5c2.2 1.7 4 3.8 5.5 6.3 3.4-7.4 8.1-13.5 14.6-18.6" stroke="var(--color-pen-red)" stroke-width="3" />
        </g>
    @endif
</svg>
