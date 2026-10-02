{{-- Closes the surrounding modal when its content is clicked. --}}
<div x-data x-on:click="$el.closest('dialog')?.close()" {{ $attributes->class('contents') }}>
    {{ $slot }}
</div>
