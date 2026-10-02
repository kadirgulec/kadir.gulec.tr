{{-- variant="strong" for the main column; align="end" for numbers and actions. --}}
@props([
    'variant' => null,
    'align' => 'start',
])

<td {{ $attributes->class([
    'px-4 py-3 align-middle',
    'font-semibold text-zinc-900 dark:text-white' => $variant === 'strong',
    'text-zinc-600 dark:text-zinc-400' => $variant !== 'strong',
    'text-right whitespace-nowrap' => $align === 'end',
]) }}>{{ $slot }}</td>
