@props(['value', 'max', 'label', 'marker' => null, 'markerLabel' => 'bugün'])

{{--
    A sketchy progress bar, filled in with pencil hatching in the section color.
    The optional marker (0..1) draws a thin "today" line, so the bar answers "am I on track?".
--}}
@php($percent = $max > 0 ? min(100, round($value / $max * 100)) : 0)

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <div
        role="progressbar"
        aria-label="{{ $label }}"
        aria-valuemin="0"
        aria-valuemax="{{ $max }}"
        aria-valuenow="{{ $value }}"
        class="h-3 w-full overflow-hidden rounded-[255px_15px_225px_15px/15px_225px_15px_255px] border-[1.5px] border-ink/55"
    >
        <div
            class="h-full bg-[repeating-linear-gradient(-55deg,var(--color-section-ink)_0_1.6px,transparent_1.6px_4.5px)]"
            style="width: {{ $percent }}%"
        ></div>
    </div>

    @if ($marker !== null)
        <div class="pointer-events-none absolute -top-1.5 -bottom-1.5 w-0.5 -translate-x-1/2 rounded-full bg-pen-red" style="left: {{ round($marker * 100, 1) }}%" aria-hidden="true">
            <span class="absolute top-full left-1/2 mt-0.5 -translate-x-1/2 font-hand text-sm leading-none whitespace-nowrap text-pen-red">{{ $markerLabel }}</span>
        </div>
    @endif
</div>
