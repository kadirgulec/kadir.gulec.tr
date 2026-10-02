{{--
    Label, description, help tooltip and error around a form control.
    The error id is "{for}-error" and the description id "{for}-description",
    so controls can point aria-describedby at them.
    The help text goes in the "help" slot (or the help attribute).
--}}
@props([
    'label' => null,
    'description' => null,
    'error' => null,
    'for' => null,
    'help' => null,
])

<div {{ $attributes->class('space-y-1.5') }}>
    @if ($label || $help)
        <div class="flex items-center gap-1.5">
            @if ($label)
                <label @if ($for) for="{{ $for }}" @endif class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $label }}</label>
            @endif

            @if ($help)
                <x-admin.tooltip :label="($label ? $label.': ' : '').'yardım'">{{ $help }}</x-admin.tooltip>
            @endif
        </div>
    @endif

    @if ($description)
        <p @if ($for) id="{{ $for }}-description" @endif class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    {{ $slot }}

    <x-admin.error :message="$error" :id="$for ? $for.'-error' : null" />
</div>
