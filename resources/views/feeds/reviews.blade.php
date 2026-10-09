{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="tr">
    <title>Kadir Gülec · Aylık değerlendirme</title>
    <subtitle>Her ayın sonunda dürüst bir bakış.</subtitle>
    <id>{{ route('goals.reviews.index') }}</id>
    <link rel="alternate" type="text/html" href="{{ route('goals.reviews.index') }}" />
    <link rel="self" type="application/atom+xml" href="{{ route('goals.reviews.feed') }}" />
    <updated>{{ $updated->toAtomString() }}</updated>
    <author>
        <name>Kadir Gülec</name>
        <uri>{{ route('about') }}</uri>
    </author>
    <icon>{{ url('/favicon.svg') }}</icon>

    @foreach ($reviews as $review)
        @php
            // Summary, score and the three lists; the number tiles stay on the site (goal titles may be censored).
            $html = filled($review->summary) ? '<p>'.e($review->summary).'</p>' : '';
            $html .= $review->score !== null ? '<p><strong>Ayın puanı: '.$review->score.' / 10</strong></p>' : '';

            foreach ([
                'good' => 'İyi giden',
                'hard' => 'Zorlandığım',
                'try' => \App\Support\TurkishDate::inMonth($review->month->addMonth()).' deneyeceğim',
            ] as $kind => $heading) {
                $items = $review->items->where('kind', \App\Enums\ReviewItemKind::from($kind));

                if ($items->isNotEmpty()) {
                    $html .= '<h3>'.e($heading).'</h3><ul>'.$items->map(fn ($item) => '<li>'.$item->body_html.'</li>')->implode('').'</ul>';
                }
            }

            $html .= '<p><a href="'.e(url($review->publicPath())).'">Rakamlarla birlikte sitede oku →</a></p>';
        @endphp
        <entry>
            <title>{{ $review->title() }}</title>
            <id>{{ url($review->publicPath()) }}</id>
            <link rel="alternate" type="text/html" href="{{ url($review->publicPath()) }}" />
            <published>{{ $review->published_at?->toAtomString() }}</published>
            <updated>{{ ($review->updated_at ?? $review->published_at)?->toAtomString() }}</updated>
            <content type="html">{{ $html }}</content>
        </entry>
    @endforeach
</feed>
