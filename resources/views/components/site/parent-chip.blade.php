@props(['parent'])

{{-- "↑ Kendi ürünüm": the bigger goal this one serves. Jumps to it on the same page. --}}
<a
    href="{{ $parent['anchor'] }}"
    {{ $attributes->merge(['class' => 'inline-flex max-w-full items-center gap-1 rounded-full border border-dashed border-section-ink/50 px-2 py-0.5 font-hand text-base leading-tight text-section-ink hover:border-solid hover:bg-section/15']) }}
>
    <span aria-hidden="true">↑</span>
    <span class="sr-only">Üst hedef:</span>
    @if ($parent['title'])
        <span class="truncate">{{ $parent['title'] }}</span>
    @else
        <x-site.censored :length="$parent['titleLength']" />
    @endif
</a>
