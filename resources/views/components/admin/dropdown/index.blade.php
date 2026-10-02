{{--
    A menu that opens under its trigger.
    <x-admin.dropdown> <x-slot:trigger>…</x-slot:trigger> <x-admin.dropdown.item …/> </x-admin.dropdown>
    position: bottom | top; align: end | start.
--}}
@props([
    'position' => 'bottom',
    'align' => 'end',
])

<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    {{ $attributes->class('relative inline-flex') }}
>
    <div x-ref="trigger" x-on:click="open = ! open" class="contents">
        {{ $trigger }}
    </div>

    <div
        x-cloak
        x-show="open"
        x-anchor.{{ $position }}-{{ $align }}.offset.6="$refs.trigger.firstElementChild ?? $refs.trigger"
        x-on:click.outside="open = false"
        x-on:click="if ($event.target.closest('a, button')) open = false"
        x-transition.opacity.duration.100ms
        role="menu"
        class="z-50 min-w-48 rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        {{ $slot }}
    </div>
</div>
