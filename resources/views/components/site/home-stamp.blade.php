{{--
    Mobile only: once the "kgülec" signature scrolls out of view, a small "kg"
    stamp sticks to the top left corner and leads back home (the mobile bar has
    no home tab). Shown and hidden by resources/js/site.js.
--}}
<a
    href="{{ route('home') }}"
    data-home-stamp
    aria-label="Kadir Gülec, ana sayfa"
    class="home-stamp fixed top-[max(0.75rem,env(safe-area-inset-top))] left-3 z-30 grid size-14 -rotate-6 place-items-center rounded-full bg-paper text-home-ink shadow-[1px_6px_14px_-6px_rgb(60_40_20/0.55)] lg:hidden dark:shadow-[1px_6px_14px_-4px_rgb(0_0_0/0.9)]"
>
    <x-site.logo class="stamp size-11" />
</a>
