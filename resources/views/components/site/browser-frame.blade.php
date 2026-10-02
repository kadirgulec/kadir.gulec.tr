@props(['url', 'imageUrl', 'alt'])

{{-- A project screenshot inside a small browser window. --}}
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-md border border-ink/15 bg-paper shadow-[0_8px_20px_-12px_rgb(60_40_20/0.5)] dark:shadow-[0_8px_20px_-8px_rgb(0_0_0/0.9)]']) }}>
    <div class="flex items-center gap-1.5 border-b border-ink/10 bg-paper-deep px-3 py-2" aria-hidden="true">
        <span class="size-2 rounded-full bg-pen-red/60"></span>
        <span class="size-2 rounded-full bg-projects/70"></span>
        <span class="size-2 rounded-full bg-goals/70"></span>
        <span class="ml-2 truncate rounded bg-paper px-2 py-0.5 font-mono text-[10px] text-ink-faint">{{ parse_url($url, PHP_URL_HOST) }}</span>
    </div>
    <img src="{{ $imageUrl }}" alt="{{ $alt }}" class="block aspect-[16/10] w-full object-cover object-top" loading="lazy" />
</div>
