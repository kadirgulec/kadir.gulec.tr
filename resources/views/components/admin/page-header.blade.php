{{-- Title row of an admin page: heading, optional description and actions on the right. --}}
@props([
    'heading',
    'description' => null,
    'dot' => null,
])

<div {{ $attributes->class('mb-6 flex flex-wrap items-end justify-between gap-4') }}>
    <div class="space-y-1">
        <div class="flex items-center gap-2.5">
            @if ($dot)
                <span class="h-6 w-1.5 rounded-full {{ $dot }}" aria-hidden="true"></span>
            @endif
            <x-admin.heading level="1" size="xl">{{ $heading }}</x-admin.heading>
        </div>

        @if ($description)
            <x-admin.text>{{ $description }}</x-admin.text>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
