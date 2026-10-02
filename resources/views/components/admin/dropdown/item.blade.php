{{-- A menu entry: a link with href, otherwise a button. variant="danger" for destructive entries. --}}
@props([
    'href' => null,
    'icon' => null,
    'variant' => null,
    'type' => 'button',
])

@php
    $classes = [
        'flex w-full cursor-pointer items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-sm font-semibold transition',
        $variant === 'danger'
            ? 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10'
            : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class($classes) }}>
        @if ($icon) <x-admin.icon :name="$icon" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" role="menuitem" {{ $attributes->class($classes) }}>
        @if ($icon) <x-admin.icon :name="$icon" /> @endif
        {{ $slot }}
    </button>
@endif
