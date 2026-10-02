@props(['days', 'mobileDays' => 14])

{{--
    "Don't break the chain": one ring per day, oldest first.
    done = a solid link, missed = a broken link, excused = a link patched with tape.
    On small screens only the newest $mobileDays links are shown so the chain never overflows.
--}}
@php
    $doneCount = collect($days)->filter(fn (string $day): bool => $day !== 'missed')->count();
    $firstMobileIndex = count($days) - $mobileDays;
@endphp

<ol class="reveal flex items-center" aria-label="Son {{ count($days) }} gün: {{ $doneCount }} gün tamam" {{ $attributes }}>
    @foreach ($days as $index => $day)
        <li @class(['-ml-1.5 first:ml-0', 'max-sm:hidden' => $index < $firstMobileIndex, 'max-sm:ml-0' => $index === $firstMobileIndex]) style="--i: {{ $index }}">
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
