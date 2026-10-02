{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="tr">
    <title>Kadir Gülec · Yazılar</title>
    <subtitle>Kod, kariyer ve arada kalan her şey.</subtitle>
    <id>{{ route('posts.index') }}</id>
    <link rel="alternate" type="text/html" href="{{ route('posts.index') }}" />
    <link rel="self" type="application/atom+xml" href="{{ route('posts.feed') }}" />
    <updated>{{ $updated->toAtomString() }}</updated>
    <author>
        <name>Kadir Gülec</name>
        <uri>{{ route('about') }}</uri>
    </author>
    <icon>{{ url('/favicon.svg') }}</icon>

    @foreach ($posts as $post)
        <entry>
            <title>{{ $post->title }}</title>
            <id>{{ route('posts.show', $post->slug) }}</id>
            <link rel="alternate" type="text/html" href="{{ route('posts.show', $post->slug) }}" />
            <published>{{ $post->published_at?->toAtomString() }}</published>
            <updated>{{ ($post->updated_at ?? $post->published_at)?->toAtomString() }}</updated>
            <summary>{{ $post->excerptText() }}</summary>
            @foreach ($post->tags as $tag)
                <category term="{{ $tag->slug }}" label="{{ $tag->name }}" />
            @endforeach
            <content type="html">{{ $markdown->toFeedHtml((string) $post->body) }}</content>
        </entry>
    @endforeach
</feed>
