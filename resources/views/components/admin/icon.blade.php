{{-- A Lucide icon in the current text color. Decorative unless a label is given. --}}
@props([
    'name',
    'label' => null,
])

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    focusable="false"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    {{ $attributes->class(['size-4 shrink-0']) }}
>{!! \App\Support\LucideIcons::markup($name) !!}</svg>
