@props(['entries'])

{{-- A dated logbook: devlogs for projects, updates for long-term goals. Newest first. --}}
<ol {{ $attributes->merge(['class' => 'relative ml-1.5 border-l-2 border-dashed border-section/60']) }}>
    @foreach ($entries as $entry)
        <li class="relative pb-7 pl-6 last:pb-0">
            <span class="absolute top-1.5 -left-[7px] size-3 rounded-full bg-section ring-4 ring-paper" aria-hidden="true"></span>
            <p class="font-mono text-xs text-ink-faint">{{ $entry['date']->locale('tr')->translatedFormat('j F Y') }}</p>
            <p class="mt-0.5">{{ $entry['text'] }}</p>
        </li>
    @endforeach
</ol>
