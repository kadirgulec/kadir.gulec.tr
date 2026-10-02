{{-- Small status label. color: zinc | green | yellow | red | blue | accent | posts | watched | goals | projects --}}
@props([
    'color' => 'zinc',
    'icon' => null,
])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-bold whitespace-nowrap',
    match ($color) {
        'green' => 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-300',
        'yellow' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
        'blue' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
        'accent' => 'bg-accent-soft text-accent',
        'posts' => 'bg-section-posts/15 text-[#a8452b] dark:text-section-posts',
        'watched' => 'bg-section-watched/15 text-[#c42452] dark:text-section-watched',
        'goals' => 'bg-section-goals/20 text-[#4f7000] dark:text-section-goals',
        'projects' => 'bg-section-projects/20 text-[#8a5800] dark:text-section-projects',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    },
]) }}>
    @if ($icon) <x-admin.icon :name="$icon" class="size-3" /> @endif
    {{ $slot }}
</span>
