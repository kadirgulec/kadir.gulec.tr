{{--
    A sheet of paper over the page (native <dialog>). Same events as the admin
    modal: $dispatch('modal-show', { name }) / 'modal-close'.
--}}
@props([
    'name',
    'heading' => null,
])

<dialog
    wire:ignore.self
    x-data
    x-on:modal-show.window="if ($event.detail.name === @js($name)) $el.showModal()"
    x-on:modal-close.window="if (! $event.detail?.name || $event.detail.name === @js($name)) $el.close()"
    x-on:click="if ($event.target === $el) $el.close()"
    @if ($heading) aria-labelledby="modal-{{ $name }}-heading" @endif
    {{ $attributes->class('paper m-auto w-[calc(100%-2rem)] max-w-md rounded-[3px] p-0 text-ink shadow-2xl backdrop:bg-desk/70 backdrop:backdrop-blur-[2px]') }}
>
    <div class="space-y-5 p-6 sm:p-8">
        @if ($heading)
            <h2 id="modal-{{ $name }}-heading" class="pr-8 font-display text-2xl font-extrabold">{{ $heading }}</h2>
        @endif

        {{ $slot }}
    </div>

    <button type="button" x-on:click="$el.closest('dialog').close()" class="absolute top-4 right-4 grid size-9 cursor-pointer place-items-center rounded-full text-ink-faint hover:text-ink" aria-label="Kapat">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">{!! \App\Support\LucideIcons::markup('x') !!}</svg>
    </button>
</dialog>
