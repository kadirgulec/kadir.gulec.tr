{{-- An on/off switch: a real checkbox with role="switch", styled through peer classes. --}}
@props([
    'label' => null,
    'description' => null,
])

@php
    $field = \App\Support\FormControl::name($attributes);
    $id = \App\Support\FormControl::id($attributes, $field);
@endphp

<div {{ $attributes->only('class')->class('flex items-start justify-between gap-4') }}>
    @if ($label)
        <div>
            <label for="{{ $id }}" class="cursor-pointer text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $label }}</label>

            @if ($description)
                <p id="{{ $id }}-description" class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>
    @endif

    <span class="relative inline-flex shrink-0">
        <input
            type="checkbox"
            role="switch"
            id="{{ $id }}"
            @if ($description) aria-describedby="{{ $id }}-description" @endif
            {{ $attributes->except(['class', 'id'])->class('peer absolute inset-0 z-10 cursor-pointer opacity-0') }}
        />
        <span class="h-6 w-11 rounded-full bg-zinc-300 transition peer-checked:bg-accent peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent dark:bg-zinc-700" aria-hidden="true"></span>
        <span class="pointer-events-none absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5 dark:peer-checked:bg-zinc-900" aria-hidden="true"></span>
    </span>
</div>
