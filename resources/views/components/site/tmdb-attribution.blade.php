{{--
    TMDB's required attribution: their logo (unchanged, and less prominent than
    the site's own branding) and the notice that TMDB does not endorse the site.
--}}
@props([
    'compact' => false,
])

<p {{ $attributes->class(['flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-ink-faint', 'text-[10px]' => $compact, 'text-[11px]' => ! $compact]) }}>
    <a href="https://www.themoviedb.org" rel="noopener" class="shrink-0" aria-label="The Movie Database (TMDB)">
        <img src="/images/tmdb.svg" alt="TMDB" @class(['w-auto', 'h-2.5' => $compact, 'h-3' => ! $compact]) loading="lazy" />
    </a>
    <span>
        Yapım bilgileri ve afişler TMDB'den alınmıştır.
        @unless ($compact)
            Bu site TMDB tarafından onaylanmış veya desteklenmemektedir.
        @endunless
    </span>
</p>
