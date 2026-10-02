@props(['value', 'max', 'label'])

{{-- A sketchy progress bar, filled in with pencil hatching in the section color. --}}
@php($percent = $max > 0 ? min(100, round($value / $max * 100)) : 0)

<div
    role="progressbar"
    aria-label="{{ $label }}"
    aria-valuemin="0"
    aria-valuemax="{{ $max }}"
    aria-valuenow="{{ $value }}"
    {{ $attributes->merge(['class' => 'h-3 w-full overflow-hidden rounded-[255px_15px_225px_15px/15px_225px_15px_255px] border-[1.5px] border-ink/55']) }}
>
    <div
        class="h-full bg-[repeating-linear-gradient(-55deg,var(--color-section-ink)_0_1.6px,transparent_1.6px_4.5px)]"
        style="width: {{ $percent }}%"
    ></div>
</div>
