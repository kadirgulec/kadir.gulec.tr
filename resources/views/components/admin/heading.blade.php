{{-- Page or section heading. size: xl (page title) | lg | base --}}
@props([
    'level' => 2,
    'size' => 'lg',
])

@php
    $tag = 'h'.max(1, min(6, (int) $level));
@endphp

<{{ $tag }} {{ $attributes->class([
    'font-bold tracking-tight text-zinc-900 dark:text-white',
    match ($size) {
        'xl' => 'text-2xl',
        'base' => 'text-base',
        default => 'text-lg',
    },
]) }}>{{ $slot }}</{{ $tag }}>
