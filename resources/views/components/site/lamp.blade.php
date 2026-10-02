{{--
    Desk lamp theme switch. Lamp on = paper notebook, lamp off = night notebook.
    The label and aria-pressed state are kept in sync by resources/js/site.js.
--}}
<button
    type="button"
    data-lamp
    aria-pressed="true"
    aria-label="Lambayı kapat (karanlık mod)"
    title="Masa lambası"
    {{ $attributes->merge(['class' => 'group relative -mr-1 size-12 shrink-0 cursor-pointer rounded-full text-ink']) }}
>
    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-full overflow-visible" aria-hidden="true">
        <defs>
            <linearGradient id="lamp-glow" x1="0.2" y1="0" x2="0.5" y2="1">
                <stop offset="0" style="stop-color: var(--color-highlighter); stop-opacity: 0.85" />
                <stop offset="1" style="stop-color: var(--color-highlighter); stop-opacity: 0" />
            </linearGradient>
        </defs>
        {{-- Light cone, only while the lamp is on --}}
        <path d="M38 20.5 31.5 28 29 52h29V29Z" fill="url(#lamp-glow)" stroke="none" class="transition-opacity duration-300 dark:opacity-0" />
        {{-- Base and arm --}}
        <path d="M8 44.5c3.5-1 9.5-1.2 14.5-.2" />
        <path d="m15 44 -2.6-12.5L23 19.5" />
        <circle cx="12.4" cy="31.5" r="1.6" fill="currentColor" stroke="none" />
        {{-- Head, tilts a little on hover --}}
        <g class="origin-[23px_19px] transition-transform duration-300 group-hover:rotate-6 motion-reduce:transition-none">
            <path d="M22 16.5 26 12.5 38.5 20.5 31.5 28Z" fill="var(--color-paper)" />
            <circle cx="34.5" cy="24.5" r="2.1" fill="var(--color-highlighter)" stroke="none" class="transition-opacity duration-300 dark:opacity-0" />
        </g>
    </svg>
</button>
