@props(['post'])

{{-- A post in a list: date, title, a dotted leader and the reading time. --}}
<a href="{{ $post['url'] }}" {{ $attributes->class('group flex items-baseline gap-3') }}>
    <span class="w-12 shrink-0 font-mono text-xs text-ink-faint">{{ $post['publishedAt']->format('d.m') }}</span>
    <span class="font-display text-lg leading-snug font-semibold group-hover:text-section-ink">{{ $post['title'] }}</span>
    <span class="mb-1 hidden min-w-8 flex-1 border-b-2 border-dotted border-rule sm:block" aria-hidden="true"></span>
    <span class="shrink-0 font-mono text-xs text-ink-faint max-sm:ml-auto">{{ $post['readingMinutes'] }} dk</span>
</a>
