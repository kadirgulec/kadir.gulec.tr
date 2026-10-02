{{-- A sidebar entry. "dot" adds a section color dot, "current" marks the open page. --}}
@props([
    'href',
    'icon' => null,
    'current' => false,
    'dot' => null,
    'external' => false,
])

<a
    href="{{ $href }}"
    @if ($current) aria-current="page" @endif
    @if ($external) target="_blank" rel="noopener" @else wire:navigate @endif
    {{ $attributes->class([
        'group flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm font-semibold transition',
        'bg-white text-zinc-900 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-white dark:ring-zinc-700' => $current,
        'text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-white' => ! $current,
    ]) }}
>
    @if ($icon)
        <x-admin.icon :name="$icon" class="{{ $current ? 'text-accent' : 'text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300' }}" />
    @endif

    <span class="flex-1">{{ $slot }}</span>

    @if ($dot)
        <span class="size-2 rounded-full {{ $dot }}" aria-hidden="true"></span>
    @endif

    @if ($external)
        <x-admin.icon name="arrow-up-right" class="size-3.5 text-zinc-400" />
    @endif
</a>
