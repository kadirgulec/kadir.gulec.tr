{{--
    An ⓘ button with a short help text. Opens on hover, on keyboard focus and
    on tap (touch screens have no hover); Escape and leaving close it.
--}}
@props([
    'label' => 'Yardım',
    'icon' => 'info',
])

<span
    x-data="{ open: false }"
    x-id="['tooltip']"
    x-on:mouseenter="open = true"
    x-on:mouseleave="open = false"
    x-on:keydown.escape="open = false"
    {{ $attributes->class('inline-flex') }}
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = true"
        x-on:focus="open = true"
        x-on:blur="open = false"
        x-bind:aria-describedby="$id('tooltip')"
        x-bind:aria-expanded="open"
        aria-label="{{ $label }}"
        class="grid size-5 cursor-help place-items-center rounded-full text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200"
    >
        <x-admin.icon :name="$icon" class="size-4" />
    </button>

    <div
        x-cloak
        x-show="open"
        x-anchor.bottom-start.offset.6="$refs.trigger"
        x-on:click.outside="open = false"
        x-bind:id="$id('tooltip')"
        role="tooltip"
        class="z-50 w-max max-w-xs rounded-lg bg-zinc-900 p-3 text-xs leading-relaxed text-zinc-100 shadow-xl sm:max-w-sm dark:bg-zinc-100 dark:text-zinc-900"
    >
        {{ $slot }}
    </div>
</span>
