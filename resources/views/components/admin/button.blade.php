{{--
    Button or link that looks like a button.
    variant: primary | outline | ghost | danger | subtle; size: sm | base.
    Shows a spinner while Livewire runs the request it triggered
    (for type="submit": while its form is submitting).
--}}
@props([
    'variant' => 'outline',
    'size' => 'base',
    'icon' => null,
    'iconTrailing' => null,
    'href' => null,
    'type' => 'button',
    'square' => false,
])

@php
    $isSubmit = $href === null && $type === 'submit';

    $classes = [
        'group inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-lg font-semibold whitespace-nowrap transition select-none',
        'disabled:cursor-not-allowed disabled:opacity-50 data-loading:pointer-events-none data-loading:opacity-75',
        $isSubmit ? 'in-data-loading:pointer-events-none in-data-loading:opacity-75' : '',
        match ($size) {
            'sm' => $square ? 'size-8 text-sm' : 'h-8 px-3 text-sm',
            default => $square ? 'size-10 text-sm' : 'h-10 px-4 text-sm',
        },
        match ($variant) {
            'primary' => 'bg-accent text-accent-on shadow-xs hover:bg-accent-hover',
            'danger' => 'bg-red-600 text-white shadow-xs hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400 dark:text-zinc-950',
            'ghost' => 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white',
            'subtle' => 'bg-zinc-100 text-zinc-800 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700',
            default => 'border border-zinc-300 bg-white text-zinc-800 shadow-xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800',
        },
    ];

    $spinnerShown = $isSubmit ? 'in-data-loading:inline-block' : 'group-data-loading:inline-block';
    $iconHidden = $isSubmit ? 'in-data-loading:hidden' : 'group-data-loading:hidden';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-admin.icon :name="$icon" /> @endif
        {{ $slot }}
        @if ($iconTrailing) <x-admin.icon :name="$iconTrailing" /> @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        <x-admin.icon name="loader-circle" class="hidden animate-spin {{ $spinnerShown }}" />
        @if ($icon) <x-admin.icon :name="$icon" class="{{ $iconHidden }}" /> @endif
        {{ $slot }}
        @if ($iconTrailing) <x-admin.icon :name="$iconTrailing" /> @endif
    </button>
@endif
