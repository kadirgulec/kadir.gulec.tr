@props(['book'])

{{-- A book on the "currently reading" shelf, with the pencil progress in percent. --}}
<li {{ $attributes->class('flex gap-4 rounded-sm bg-paper-deep p-4 shadow-[0_8px_18px_-14px_rgb(60_40_20/0.5)] dark:shadow-[0_8px_18px_-10px_rgb(0_0_0/0.85)]') }}>
    <x-site.book-cover :title="$book['title']" :image-url="$book['coverUrl']" :colors="$book['coverColors']" :framed="false" class="w-14 self-start" />

    <div class="min-w-0 flex-1">
        <a href="{{ $book['url'] }}" class="block truncate font-display text-lg font-semibold hover:text-section-ink">{{ $book['title'] }}</a>
        <p class="truncate font-hand text-lg leading-tight text-ink-soft">{{ $book['status']->emoji() }} {{ $book['status']->label() }} · {{ $book['author'] }}</p>

        <div class="mt-2 flex items-center gap-3">
            <x-site.pencil-progress
                :value="$book['progress'] ?? 0"
                :max="100"
                :label="$book['title'].' okuma ilerlemesi'"
                class="flex-1"
            />
            <span class="shrink-0 font-mono text-xs text-ink-soft">%{{ $book['progress'] ?? 0 }}</span>
        </div>
    </div>
</li>
