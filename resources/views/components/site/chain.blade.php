@props(['days'])

{{--
    "Don't break the chain": one ring per day, oldest first.
    done = a solid link, missed = a broken link, excused = a link patched with tape.

    The chain fits its own box, not the screen: the list is a size container and each
    ring hides itself (oldest first) when the box is too narrow to show it. That works in
    any column layout, at any viewport width.
--}}
@php
    $doneCount = collect($days)->filter(fn (string $day): bool => $day !== 'missed')->count();

    // Each ring is 24px wide and overlaps the previous one by 6px; the list has 6px padding.
    // Ring k (1 = newest) is shown only when the box is at least 6 + 18k px wide.
    // Literal class names, so Tailwind can find and generate them.
    $hideBelow = [
        1 => '@max-[24px]:hidden',
        2 => '@max-[42px]:hidden',
        3 => '@max-[60px]:hidden',
        4 => '@max-[78px]:hidden',
        5 => '@max-[96px]:hidden',
        6 => '@max-[114px]:hidden',
        7 => '@max-[132px]:hidden',
        8 => '@max-[150px]:hidden',
        9 => '@max-[168px]:hidden',
        10 => '@max-[186px]:hidden',
        11 => '@max-[204px]:hidden',
        12 => '@max-[222px]:hidden',
        13 => '@max-[240px]:hidden',
        14 => '@max-[258px]:hidden',
        15 => '@max-[276px]:hidden',
        16 => '@max-[294px]:hidden',
        17 => '@max-[312px]:hidden',
        18 => '@max-[330px]:hidden',
        19 => '@max-[348px]:hidden',
        20 => '@max-[366px]:hidden',
        21 => '@max-[384px]:hidden',
    ];
@endphp

<ol class="reveal @container flex items-center pl-1.5" aria-label="Son {{ count($days) }} gün: {{ $doneCount }} gün tamam" {{ $attributes }}>
    @foreach ($days as $index => $day)
        <li class="-ml-1.5 shrink-0 {{ $hideBelow[count($days) - $index] ?? 'hidden' }}" style="--i: {{ $index }}">
            <svg viewBox="0 0 24 16" class="h-4 w-6 overflow-visible" aria-hidden="true">
                <g transform="rotate({{ $index % 2 === 0 ? -10 : 10 }} 12 8)">
                    @if ($day === 'missed')
                        <ellipse cx="12" cy="8" rx="8.5" ry="5" fill="none" stroke="var(--color-ink-faint)" stroke-width="2.2" stroke-dasharray="20 7" stroke-linecap="round" />
                    @else
                        <ellipse cx="12" cy="8" rx="8.5" ry="5" fill="none" stroke="var(--color-section)" stroke-width="3" />
                    @endif
                </g>

                @if ($day === 'excused')
                    <rect x="9.5" y="1" width="5" height="14" rx="0.5" transform="rotate(28 12 8)" fill="var(--color-ink-faint)" opacity="0.55" />
                @endif
            </svg>
        </li>
    @endforeach
</ol>
