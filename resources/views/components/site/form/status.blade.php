{{-- A handwritten "done" note, e.g. after saving or sending a link. --}}
@props([
    'message' => null,
])

@if (filled($message))
    <p role="status" {{ $attributes->class('flex items-center gap-2 font-hand text-xl leading-tight font-bold text-section-ink') }}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true">{!! \App\Support\LucideIcons::markup('check') !!}</svg>
        <span>{{ $message }}</span>
    </p>
@endif
