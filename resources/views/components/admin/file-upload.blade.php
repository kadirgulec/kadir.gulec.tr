{{--
    Drop zone for Livewire uploads: drag files onto it, paste them or pick
    them with the button. "name" is the Livewire property; "preview" an image
    URL to show (the stored file or the temporary upload). Extra content
    (e.g. a gallery) goes in the slot.
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
    $options = json_encode(['accept' => $accept, 'multiple' => (bool) $multiple]);
@endphp

<x-admin.field :label="$label" :description="$description" :help="$help" :error="$error" {{ $attributes->only('class') }}>
    <div
        wire:drop.file="$upload('{{ $name }}', {{ $options }})"
        class="relative rounded-xl border-2 border-dashed border-zinc-300 bg-white p-4 transition data-dragging:border-accent data-dragging:bg-accent-soft dark:border-zinc-700 dark:bg-zinc-900"
    >
        @if ($preview)
            <img src="{{ $preview }}" alt="" class="mx-auto mb-4 max-h-56 rounded-lg object-contain shadow-sm" />
        @endif

        {{ $slot }}

        <div class="flex flex-col items-center gap-2 text-center text-sm text-zinc-600 dark:text-zinc-400">
            <x-admin.icon name="upload" class="size-5 text-zinc-400" />
            <p>
                Sürükleyip bırak ya da
                <button type="button" wire:click="$upload('{{ $name }}', {{ $options }})" class="cursor-pointer font-semibold text-accent underline underline-offset-4">dosya seç</button>
            </p>
            <p class="text-xs text-zinc-500">JPEG, PNG, WebP, AVIF · en fazla 10 MB · WebP'ye çevrilir, konum bilgisi silinir</p>
            <p wire:loading wire:target="{{ $name }}" class="flex items-center gap-1.5 font-semibold text-accent">
                <x-admin.icon name="loader-circle" class="animate-spin" /> Yükleniyor…
            </p>
        </div>
    </div>
</x-admin.field>
