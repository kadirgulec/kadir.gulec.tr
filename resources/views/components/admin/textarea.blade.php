{{-- Multi-line text with label, description, help tooltip and error. --}}
@props([
    'label' => null,
    'description' => null,
    'help' => null,
    'name' => null,
    'rows' => 4,
    'mono' => false,
    'controlClass' => '',
])

@php
    $field = \App\Support\FormControl::name($attributes, $name);
    $id = \App\Support\FormControl::id($attributes, $field);
    $error = $field !== null ? $errors->first($field) : null;
    $describedBy = \App\Support\FormControl::describedBy($id, $description, $error);
@endphp

<x-admin.field :label="$label" :description="$description" :help="$help" :error="$error" :for="$id" {{ $attributes->only('class') }}>
    @isset($toolbar)
        {{ $toolbar }}
    @endisset

    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except(['class', 'id'])->class(['control resize-y leading-relaxed', 'font-mono text-[13px]' => $mono, $controlClass]) }}
    >{{ $slot }}</textarea>
</x-admin.field>
