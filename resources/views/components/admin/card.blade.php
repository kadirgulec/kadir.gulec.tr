{{-- A white panel. Optional "heading" and "actions" slots form its header. --}}
@props([
    'padding' => 'p-5',
])

<section {{ $attributes->class(['rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if (isset($heading) || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-800">
            @isset($heading)
                <h2 class="text-base font-bold text-zinc-900 dark:text-white">{{ $heading }}</h2>
            @endisset

            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</section>
