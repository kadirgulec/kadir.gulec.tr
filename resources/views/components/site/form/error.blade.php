{{-- A correction in red pen under a form field. --}}
@props([
    'message' => null,
])

@if (filled($message))
    <p {{ $attributes->class('font-hand text-xl leading-tight font-bold text-pen-red') }}>{{ $message }}</p>
@endif
