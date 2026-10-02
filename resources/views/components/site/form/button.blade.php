{{--
    Notebook button: ink stamp (primary), outlined (secondary), red pen (danger)
    or a plain underlined link look (link). Renders an <a> when href is given.
--}}
@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = [
        'inline-flex cursor-pointer items-center justify-center gap-2 font-bold transition select-none disabled:cursor-not-allowed disabled:opacity-60 data-loading:opacity-70',
        $type === 'submit' ? 'in-data-loading:opacity-70 in-data-loading:pointer-events-none' : '',
        match ($variant) {
            'secondary' => 'rounded-md border-2 border-ink px-4 py-2 text-ink hover:bg-ink hover:text-paper',
            'danger' => 'rounded-md bg-pen-red px-4 py-2.5 text-paper shadow-[2px_2px_0_var(--color-ink)] hover:-translate-y-px dark:text-desk',
            'link' => 'text-sm text-ink-soft underline decoration-section decoration-2 underline-offset-4 hover:text-ink',
            default => 'rounded-md bg-ink px-4 py-2.5 text-paper shadow-[2px_2px_0_var(--color-section)] hover:-translate-y-px hover:shadow-[3px_3px_0_var(--color-section)]',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
