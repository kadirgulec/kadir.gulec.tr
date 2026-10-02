{{-- A checkbox with its label on the right and an optional description below. --}}
@props([
    'label' => null,
    'description' => null,
    'name' => null,
])

@php
    $field = \App\Support\FormControl::name($attributes, $name);
    $value = $attributes->get('value');
    $id = \App\Support\FormControl::id($attributes, $field !== null && $value !== null ? $field.'-'.$value : $field);
    $error = $field !== null ? $errors->first($field) : null;
@endphp

<div {{ $attributes->only('class')->class('space-y-1') }}>
    <div class="flex items-start gap-2.5">
        <input
            type="checkbox"
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($description) aria-describedby="{{ $id }}-description" @endif
            {{ $attributes->except(['class', 'id'])->class('mt-0.5 size-4 shrink-0 cursor-pointer rounded border-zinc-300 accent-accent dark:border-zinc-600') }}
        />

        @if ($label)
            <div>
                <label for="{{ $id }}" class="cursor-pointer text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $label }}</label>

                @if ($description)
                    <p id="{{ $id }}-description" class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>
        @endif
    </div>

    <x-admin.error :message="$error" />
</div>
