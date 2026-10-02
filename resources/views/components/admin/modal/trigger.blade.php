{{-- Opens the modal with the given name when its content is clicked. --}}
@props([
    'name',
])

<div x-data x-on:click="$dispatch('modal-show', { name: @js($name) })" {{ $attributes->class('contents') }}>
    {{ $slot }}
</div>
