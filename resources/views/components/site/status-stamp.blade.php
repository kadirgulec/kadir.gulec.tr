@props(['status'])

{{-- Project status, pressed on like a rubber stamp. --}}
@php
    [$text, $color] = match ($status) {
        'live' => ['YAYINDA', 'text-goals-ink'],
        'archived' => ['ARŞİV', 'text-ink-faint'],
        default => ['YAPIM AŞAMASINDA', 'text-projects-ink'],
    };
@endphp

<span {{ $attributes->merge(['class' => "stamp inline-block -rotate-6 rounded-[4px] border-[2.5px] border-current px-2 py-0.5 font-mono text-[11px] font-bold tracking-[0.18em] whitespace-nowrap {$color}"]) }}>
    {{ $text }}
</span>
