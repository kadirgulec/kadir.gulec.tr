{{--
    Drop zone for Livewire uploads: drag files onto it or pick them with the
    button. "name" is the Livewire property; "preview" an image URL to show
    (the stored file or the temporary upload). Extra content (e.g. a gallery)
    goes in the slot.

    Both gestures end in one hidden <input type="file" wire:model>, Livewire's
    stable upload path: the button opens its picker, and a drop hands the
    dropped files to it (fileDropZone in resources/js/admin.js). $upload with
    an options object and wire:drop only exist in Livewire's unreleased 4.x
    branch; in 4.4 the options object is sent as the file and nothing uploads.
--}}
@props([
    'name',
    'label' => null,
    'description' => null,
    'help' => null,
    'accept' => 'image/jpeg,image/png,image/webp,image/avif,image/gif',
    'multiple' => false,
    'preview' => null,
])

@php
    $error = $errors->first($name) ?: $errors->first($name.'.*');
@endphp

<x-admin.field :label="$label" :description="$description" :help="$help" :error="$error" data-upload-field {{ $attributes->only('class') }}>
    <div
        data-upload-zone
        x-data="fileDropZone"
        x-on:dragenter="enter($event)"
        x-on:dragover="over($event)"
        x-on:dragleave="leave($event)"
        x-on:drop="drop($event)"
        x-bind:data-dragging="dragging ? '' : null"
        class="relative rounded-xl border-2 border-dashed border-zinc-300 bg-white p-4 transition data-dragging:border-accent data-dragging:bg-accent-soft dark:border-zinc-700 dark:bg-zinc-900"
    >
        <input type="file" x-ref="input" wire:model="{{ $name }}" x-on:livewire-upload-finish="$el.value = ''" accept="{{ $accept }}" @if ($multiple) multiple @endif class="hidden" tabindex="-1" aria-hidden="true" />

        @if ($preview)
            <img src="{{ $preview }}" alt="" class="mx-auto mb-4 max-h-56 rounded-lg object-contain shadow-sm" />
        @endif

        {{ $slot }}

        <div class="flex flex-col items-center gap-2 text-center text-sm text-zinc-600 dark:text-zinc-400">
            <x-admin.icon name="upload" class="size-5 text-zinc-400" />
            <p>
                Sürükleyip bırak ya da
                <button type="button" x-on:click="$refs.input.click()" class="cursor-pointer font-semibold text-accent underline underline-offset-4">dosya seç</button>
            </p>
            <p class="text-xs text-zinc-500">JPEG, PNG, WebP, AVIF · en fazla 10 MB · WebP'ye çevrilir, konum bilgisi silinir</p>
            <p wire:loading wire:target="{{ $name }}" class="flex items-center gap-1.5 font-semibold text-accent">
                <x-admin.icon name="loader-circle" class="animate-spin" /> Yükleniyor…
            </p>
        </div>
    </div>
</x-admin.field>

