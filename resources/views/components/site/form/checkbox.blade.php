@props([
    'label',
    'name' => null,
])

@php
    $id = \App\Support\FormControl::id($attributes, \App\Support\FormControl::name($attributes, $name));
@endphp

<div {{ $attributes->only('class')->class('flex items-center gap-2.5') }}>
    <input
        type="checkbox"
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->except(['class', 'id'])->class('size-4.5 shrink-0 cursor-pointer accent-(--color-section-ink)') }}
    />
    <label for="{{ $id }}" class="cursor-pointer text-sm font-semibold text-ink-soft">{{ $label }}</label>
</div>
