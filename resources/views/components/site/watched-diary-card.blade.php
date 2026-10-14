@props(['entry'])

{{-- A viewing of a film or series in the diary (İzlediklerim and Rafım). --}}
<x-site.diary-card
    :url="$entry['url']"
    :title="$entry['title']"
    :kicker="collect([$entry['type']->label(), $entry['year'], $entry['creator']])->filter()->implode(' · ')"
    :rating="$entry['rating']"
    :is-favorite="$entry['isFavorite']"
    :excerpt="$entry['hasReview'] ? $entry['reviewExcerpt'] : null"
    :badges="array_values(array_filter([
        $entry['hasReview'] ? ['label' => '✍️ yorum', 'strong' => true] : null,
        $entry['isRewatch'] ? ['label' => '↻ tekrar'] : null,
        $entry['status'] ? ['label' => $entry['status']->emoji().' '.$entry['status']->label().' · S'.$entry['season'].' B'.$entry['episode']] : null,
    ]))"
    {{ $attributes }}
>
    <x-slot:cover>
        <x-site.poster :title="$entry['title']" :image-url="$entry['posterUrl']" :colors="$entry['posterColors']" :framed="false" />
    </x-slot:cover>
</x-site.diary-card>
