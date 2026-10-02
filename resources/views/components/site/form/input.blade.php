{{--
    A notebook text field: label, optional hint, red-pen error.
    Works with plain forms (name) and Livewire (wire:model); the error key is
    the name or the wire:model value. Password fields get a show/hide button.
--}}
@props([
    'label' => null,
    'hint' => null,
    'type' => 'text',
    'name' => null,
    'viewable' => false,
])

@php
    $field = \App\Support\FormControl::name($attributes, $name);
    $id = \App\Support\FormControl::id($attributes, $field);
    $error = $field !== null ? $errors->first($field) : null;
    $describedBy = \App\Support\FormControl::describedBy($id, $hint, $error);
    $isViewable = $viewable && $type === 'password';
@endphp

<div {{ $attributes->only('class')->class('space-y-1.5') }} @if ($isViewable) x-data="{ visible: false }" @endif>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-bold text-ink">{{ $label }}</label>
    @endif

    <div class="relative">
        <input
            id="{{ $id }}"
            @if ($isViewable) x-bind:type="visible ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
            @if ($name) name="{{ $name }}" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except(['class', 'id'])->class([
                'block w-full rounded-md border-2 border-rule bg-paper px-3 py-2.5 text-ink shadow-[inset_0_1px_2px_rgb(60_40_20/0.06)] transition placeholder:text-ink-faint',
                'focus:border-section-ink focus:outline-none aria-invalid:border-pen-red',
                'pr-11' => $isViewable,
            ]) }}
        />

        @if ($isViewable)
            <button
                type="button"
                x-on:click="visible = ! visible"
                x-bind:aria-label="visible ? 'Şifreyi gizle' : 'Şifreyi göster'"
                aria-controls="{{ $id }}"
                class="absolute inset-y-0 right-0 grid w-11 cursor-pointer place-items-center text-ink-faint hover:text-ink"
            >
                <svg x-show="! visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">{!! \App\Support\LucideIcons::markup('eye') !!}</svg>
                <svg x-show="visible" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">{!! \App\Support\LucideIcons::markup('eye-off') !!}</svg>
            </button>
        @endif
    </div>

    @if ($hint)
        <p id="{{ $id }}-description" class="text-sm text-ink-faint">{{ $hint }}</p>
    @endif

    <x-site.form.error :message="$error" :id="$id.'-error'" />
</div>
