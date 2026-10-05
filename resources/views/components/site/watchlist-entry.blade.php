@props(['entry', 'tilt' => 0])

{{-- One poster on the watchlist ("Sırada"): not watched yet, so it links nowhere. --}}
<li {{ $attributes->class('w-28 shrink-0 snap-start') }}>
    <x-site.poster :title="$entry['title']" :year="$entry['year']" :image-url="$entry['posterUrl']" :colors="$entry['posterColors']" :tilt="$tilt" />
    <p class="mt-3 font-display text-sm leading-tight font-semibold">{{ $entry['title'] }}</p>
    @if ($entry['note'])
        <p class="mt-1 line-clamp-3 font-hand text-lg leading-tight text-ink-soft">{{ $entry['note'] }}</p>
    @endif
</li>
