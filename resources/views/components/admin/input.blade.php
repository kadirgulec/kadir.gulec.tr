{{--
    Text-like input with label, description, help tooltip and error.
    <x-admin.input wire:model="title" label="Başlık" />
    The "class" attribute goes to the wrapper, everything else to the <input>.
--}}
@props([
    'label' => null,
    'description' => null,
    'help' => null,
    'type' => 'text',
    'icon' => null,
    'mono' => false,
])

@php
    $field = \App\Support\FormControl::name($attributes);
    $id = \App\Support\FormControl::id($attributes, $field);
    $error = $field !== null ? $errors->first($field) : null;
    $describedBy = \App\Support\FormControl::describedBy($id, $description, $error);
@endphp

<x-admin.field :label="$label" :description="$description" :help="$help" :error="$error" :for="$id" {{ $attributes->only('class') }}>
    <div class="relative">
        @if ($icon)
            <x-admin.icon :name="$icon" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-zinc-400" />
        @endif

        <input
            type="{{ $type }}"
            id="{{ $id }}"
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except(['class', 'id'])->class(['control', 'pl-9' => $icon, 'font-mono [font-variant-ligatures:none]' => $mono]) }}
        />
    </div>
</x-admin.field>
