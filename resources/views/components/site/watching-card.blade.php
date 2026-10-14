@props(['series'])

{{-- A series on the "currently watching" shelf, with the pencil progress through its season. --}}
<li {{ $attributes->class('flex gap-4 rounded-sm bg-paper-deep p-4 shadow-[0_8px_18px_-14px_rgb(60_40_20/0.5)] dark:shadow-[0_8px_18px_-10px_rgb(0_0_0/0.85)]') }}>
    <x-site.poster :title="$series['title']" :image-url="$series['posterUrl']" :colors="$series['posterColors']" :framed="false" class="w-14 self-start" />

    <div class="min-w-0 flex-1">
        <a href="{{ $series['url'] }}" class="block truncate font-display text-lg font-semibold hover:text-section-ink">{{ $series['title'] }}</a>
        <p class="font-hand text-lg leading-tight text-ink-soft">{{ $series['status']->emoji() }} {{ $series['status']->label() }}</p>

        <div class="mt-2 flex items-center gap-3">
            <x-site.pencil-progress
                :value="$series['episode']"
                :max="$series['episodeCount']"
                :label="$series['title'].' sezon ilerlemesi'"
                class="flex-1"
            />
            <span class="shrink-0 font-mono text-xs text-ink-soft">S{{ $series['season'] }} · B{{ $series['episode'] }}</span>
        </div>
    </div>
</li>
