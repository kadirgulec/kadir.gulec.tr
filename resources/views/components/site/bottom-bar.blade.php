@props(['current'])

{{-- Mobile: the divider tabs become a colored tab bar at the bottom. Home and About have no tab here (see Section::isInMobileBar()). --}}
<nav aria-label="Bölümler" class="vt-bar fixed inset-x-0 bottom-0 z-30 border-t border-rule bg-paper/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-sm lg:hidden">
    <ul class="mx-auto grid max-w-lg grid-cols-5">
        @foreach (array_filter(\App\Enums\Section::cases(), fn (\App\Enums\Section $section): bool => $section->isInMobileBar()) as $item)
            @php($isCurrent = $item === $current)

            <li data-section="{{ $item->value }}">
                <a
                    href="{{ $item->url() }}"
                    @if ($isCurrent) aria-current="page" @endif
                    @class([
                        'flex flex-col items-center gap-0.5 pb-1.5 text-[10.5px] leading-normal font-semibold',
                        'text-section-ink' => $isCurrent,
                        'text-ink-soft' => ! $isCurrent,
                    ])
                >
                    <span @class([
                        'h-1.5 w-9 rounded-b-md',
                        'bg-section' => $isCurrent,
                        'bg-section/30' => ! $isCurrent,
                    ])></span>
                    <x-site.section-icon :section="$item" class="mt-1 size-5" />
                    <span class="max-w-full truncate tracking-tight">{{ $item->label() }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
