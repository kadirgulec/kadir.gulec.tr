@props(['project'])

{{-- The project's screenshot in a browser window, or a sketched placeholder when there is none. --}}
@if ($project['imageUrl'])
    <x-site.browser-frame
        :url="$project['demoUrl'] ?? $project['repoUrl'] ?? $project['url']"
        :image-url="$project['imageUrl']"
        :srcset="$project['imageSrcset'] ?? null"
        :alt="$project['name'].' ekran görüntüsü'"
        {{ $attributes }}
    />
@else
    <div {{ $attributes->merge(['class' => 'relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-md border-2 border-dashed border-ink/20 bg-[repeating-linear-gradient(-45deg,transparent_0_10px,color-mix(in_oklab,var(--color-section)_12%,transparent)_10px_12px)]']) }}>
        <span class="rotate-[-3deg] bg-paper px-3 py-1 font-mono text-sm text-ink-soft shadow-sm">{{ $project['repoUrl'] ? '~/'.basename($project['repoUrl']) : $project['name'] }}</span>
        <span class="absolute right-3 bottom-2 font-hand text-lg text-ink-faint">ekran görüntüsü yok</span>
    </div>
@endif
