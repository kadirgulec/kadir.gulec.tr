{{--
    A native <dialog>. Open it with $dispatch('modal-show', { name }) or, from
    PHP, $this->dispatch('modal-show', name: '…'); close with 'modal-close'.
    Escape, the backdrop and x-admin.modal.close close it as well.
    wire:ignore.self keeps the "open" state while Livewire re-renders the content.
--}}
@props([
    'name',
    'heading' => null,
    'description' => null,
])

<dialog
    wire:ignore.self
    x-data
    x-on:modal-show.window="if ($event.detail.name === @js($name)) $el.showModal()"
    x-on:modal-close.window="if (! $event.detail?.name || $event.detail.name === @js($name)) $el.close()"
    x-on:click="if ($event.target === $el) $el.close()"
    aria-labelledby="modal-{{ $name }}-heading"
    {{ $attributes->class('m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-zinc-200 bg-white p-0 text-zinc-900 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100') }}
>
    <div class="space-y-4 p-6">
        @if ($heading)
            <div class="space-y-1 pr-8">
                <x-admin.heading id="modal-{{ $name }}-heading">{{ $heading }}</x-admin.heading>
                @if ($description)
                    <x-admin.text>{{ $description }}</x-admin.text>
                @endif
            </div>
        @endif

        {{ $slot }}

        @isset($footer)
            <div class="flex flex-wrap items-center justify-end gap-2 pt-2">{{ $footer }}</div>
        @endisset
    </div>

    <button type="button" x-on:click="$el.closest('dialog').close()" class="absolute top-4 right-4 grid size-8 cursor-pointer place-items-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" aria-label="Kapat">
        <x-admin.icon name="x" />
    </button>
</dialog>
