@props([
    'href',
    'external' => false,
])

<a
    href="{{ $href }}"
    @if ($external) target="_blank" rel="noopener" @endif
    {{ $attributes->class('inline-flex items-center gap-1 font-semibold text-accent underline decoration-accent/30 underline-offset-4 hover:decoration-accent') }}
>{{ $slot }}@if ($external)<x-admin.icon name="arrow-up-right" class="size-3.5" />@endif</a>
