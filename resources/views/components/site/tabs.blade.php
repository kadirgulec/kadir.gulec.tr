@props(['current'])

{{-- Desktop: colored divider tabs sticking out of the page's right edge. --}}
<nav aria-label="Bölümler" class="absolute top-0 left-full hidden h-full lg:block">
    <ul class="sticky top-10 flex flex-col gap-2 pt-20">
        @foreach (\App\Enums\Section::cases() as $item)
            @php($isCurrent = $item === $current)

            <li data-section="{{ $item->value }}">
                <a
                    href="{{ $item->url() }}"
                    @if ($isCurrent) aria-current="page" @endif
                    @class([
                        'flex w-44 items-center gap-2.5 rounded-r-xl py-2.5 pr-4 pl-5 font-display text-[15px] font-semibold transition duration-200 ease-out motion-reduce:transition-none',
                        'translate-x-0 bg-section text-section-on shadow-[3px_3px_0_rgb(0_0_0/0.08)]' => $isCurrent,
                        '-translate-x-4 bg-section/25 text-ink-soft hover:-translate-x-1 hover:bg-section/45 hover:text-ink dark:bg-section/15 dark:hover:bg-section/30' => ! $isCurrent,
                    ])
                >
                    <x-site.section-icon :section="$item" class="size-5 shrink-0" />
                    {{ $item->label() }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
