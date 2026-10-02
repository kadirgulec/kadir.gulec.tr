@props([
    'message' => null,
])

@if (filled($message))
    <p {{ $attributes->class('flex items-start gap-1.5 text-sm font-semibold text-red-600 dark:text-red-400') }}>
        <x-admin.icon name="triangle-alert" class="mt-0.5" />
        <span>{{ $message }}</span>
    </p>
@endif
