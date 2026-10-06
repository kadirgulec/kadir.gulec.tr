@props(['section'])

{{-- Small, slightly wobbly line icons, one per section. --}}
@if ($section === \App\Enums\Section::Home)
    <x-site.logo {{ $attributes }} />
@else
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
        @switch($section)
            @case(\App\Enums\Section::Posts)
                {{-- Pencil --}}
                <path d="M4.5 19.6 5.4 15 15.8 4.6c.8-.8 2.1-.8 2.9 0l.8.8c.8.8.8 2.1 0 2.9L9.1 18.7l-4.6.9Z" />
                <path d="m14 6.5 3.6 3.5" />
                <path d="M5.4 15.1c1.3.2 2.6 1.4 3.6 3.5" />
                @break
            @case(\App\Enums\Section::Notes)
                {{-- Post-it with a curled corner --}}
                <path d="M4.6 5.3c0-.6.5-1.1 1.1-1.1l12.8.2c.6 0 1 .5 1 1.1l-.2 9.3-5.4 5.2-8.3-.1c-.6 0-1-.5-1-1.1V5.3Z" />
                <path d="M19.3 14.8l-4.2.1c-.6 0-1 .5-1 1.1l-.2 3.9" />
                <path d="M8 9.1h7.6M8 12.4h5" />
                @break
            @case(\App\Enums\Section::Watched)
                {{-- Clapperboard --}}
                <path d="M4.2 10.2h15.6l-.3 8.6c0 .7-.6 1.2-1.3 1.2H5.8c-.7 0-1.3-.5-1.3-1.2l-.3-8.6Z" />
                <path d="m4 10-.6-3.1c-.1-.7.3-1.3 1-1.4l12.8-2.3c.7-.1 1.3.3 1.4 1l.4 2.3L4 10Z" />
                <path d="m8.2 5 2 3.6M12.8 4.2l2 3.6" />
                @break
            @case(\App\Enums\Section::Goals)
                {{-- Flag on a hill --}}
                <path d="M6.5 20.5V4" />
                <path d="M6.6 4.6c2.5-1.2 4.4.8 6.6.2 1.7-.4 3-1.3 4.6-1v7.4c-1.7-.2-2.9.7-4.6 1.1-2.2.5-4.1-1.4-6.6-.2" />
                <path d="M3 20.6c3.6-.4 7.2-.5 11-.1" />
                @break
            @case(\App\Enums\Section::Projects)
                {{-- Wrench --}}
                <path d="M14.4 6.1a4 4 0 0 1 5.3-1.7l-2.6 2.7.4 2.3 2.3.5 2.6-2.6a4 4 0 0 1-5.4 5.1L9.6 19.9a2.1 2.1 0 0 1-3-3l7.5-7.4a4 4 0 0 1 .3-3.4Z" />
                @break
            @case(\App\Enums\Section::About)
                {{-- Doodled smiley --}}
                <path d="M12 3.6c4.8-.2 8.5 3.4 8.4 8.2-.1 4.7-3.8 8.5-8.5 8.6-4.6.1-8.4-3.6-8.4-8.3 0-4.6 3.7-8.3 8.5-8.5Z" />
                <path d="M9 10v.6M15 10v.6" />
                <path d="M8.4 14.2c2 2.3 5.3 2.4 7.2 0" />
                @break
        @endswitch
    </svg>
@endif
