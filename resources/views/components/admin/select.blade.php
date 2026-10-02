{{--
    Native select with label and error. Options go in the slot, or pass
    :options="['value' => 'Label']". An optional placeholder adds an empty first option.
--}}
@props([
    'label' => null,
    'description' => null,
    'help' => null,
    'name' => null,
    'options' => null,
    'placeholder' => null,
])

@php
    $field = \App\Support\FormControl::name($attributes, $name);
    $id = \App\Support\FormControl::id($attributes, $field);
    $error = $field !== null ? $errors->first($field) : null;
    $describedBy = \App\Support\FormControl::describedBy($id, $description, $error);
@endphp

<x-admin.field :label="$label" :description="$description" :help="$help" :error="$error" :for="$id" {{ $attributes->only('class') }}>
    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except(['class', 'id'])->class('control appearance-none pr-9') }}
        >
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif

            @if ($options !== null)
                @foreach ($options as $value => $optionLabel)
                    <option value="{{ $value }}">{{ $optionLabel }}</option>
                @endforeach
            @endif

            {{ $slot }}
        </select>

        <x-admin.icon name="chevrons-up-down" class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-zinc-400" />
    </div>
</x-admin.field>
