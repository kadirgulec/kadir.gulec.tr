{{-- Empty state of a list: an icon, a sentence and an optional action. --}}
@props([
    'icon' => 'layers',
    'heading',
])

<div {{ $attributes->class('flex flex-col items-center gap-3 px-6 py-12 text-center') }}>
    <span class="grid size-11 place-items-center rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
        <x-admin.icon :name="$icon" class="size-5" />
    </span>
    <p class="font-bold text-zinc-900 dark:text-white">{{ $heading }}</p>

    @if ($slot->isNotEmpty())
        <div class="max-w-sm text-sm text-zinc-600 dark:text-zinc-400">{{ $slot }}</div>
    @endif

    @isset($actions)
        <div class="mt-1 flex gap-2">{{ $actions }}</div>
    @endisset
</div>
