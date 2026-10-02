@props(['length' => 12, 'label' => 'sansürlü'])

{{--
    Blacked-out text for censored goals. The real words never reach the page:
    the marker covers filler "words" of roughly the same length.
--}}
@php
    $wordLengths = [6, 4, 7, 3, 5];
    $words = [];
    $remaining = max(4, (int) $length);

    for ($index = 0; $remaining > 0; $index++) {
        $wordLength = min($remaining, $wordLengths[$index % count($wordLengths)]);
        $words[] = str_repeat('x', $wordLength);
        $remaining -= $wordLength + 1;
    }
@endphp

<span {{ $attributes }}>
    <span class="redact" aria-hidden="true">{{ implode(' ', $words) }}</span>
    <span class="sr-only">{{ $label }}</span>
</span>
