@props(['entry'])

{{-- A finished or abandoned reading in the diary (Okuduklarım and Rafım). --}}
<x-site.diary-card
    :url="$entry['url']"
    :title="$entry['title']"
    :kicker="collect(['Kitap', $entry['author'], $entry['language'] !== 'Türkçe' ? $entry['language'] : null])->filter()->implode(' · ')"
    :rating="$entry['rating']"
    :is-favorite="$entry['isFavorite']"
    :excerpt="$entry['hasReview'] ? $entry['reviewExcerpt'] : null"
    :badges="array_values(array_filter([
        $entry['hasReview'] ? ['label' => '✍️ yorum', 'strong' => true] : null,
        $entry['isReread'] ? ['label' => '↻ tekrar okuma'] : null,
        $entry['status'] === \App\Enums\ReadingStatus::Abandoned ? ['label' => $entry['status']->emoji().' '.mb_strtolower($entry['status']->label()).($entry['progress'] !== null ? ' · %'.$entry['progress'] : '')] : null,
    ]))"
    {{ $attributes }}
>
    <x-slot:cover>
        <x-site.book-cover :title="$entry['title']" :image-url="$entry['coverUrl']" :colors="$entry['coverColors']" :framed="false" />
    </x-slot:cover>
</x-site.diary-card>
