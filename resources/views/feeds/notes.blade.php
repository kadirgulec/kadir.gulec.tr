{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="tr">
    <title>Kadir Gülec · Öğrendiklerim</title>
    <subtitle>Küçük notlar, büyük birikim.</subtitle>
    <id>{{ route('notes.index') }}</id>
    <link rel="alternate" type="text/html" href="{{ route('notes.index') }}" />
    <link rel="self" type="application/atom+xml" href="{{ route('notes.feed') }}" />
    <updated>{{ $updated->toAtomString() }}</updated>
    <author>
        <name>Kadir Gülec</name>
        <uri>{{ route('about') }}</uri>
    </author>
    <icon>{{ url('/favicon.svg') }}</icon>

    @foreach ($notes as $note)
        @php($text = $note->text())
        <entry>
            <title>{{ \Illuminate\Support\Str::limit($text, 60, '…', preserveWords: true) }}</title>
            <id>{{ route('notes.show', $note->id) }}</id>
            <link rel="alternate" type="text/html" href="{{ route('notes.show', $note->id) }}" />
            <published>{{ $note->published_at?->toAtomString() }}</published>
            <updated>{{ ($note->updated_at ?? $note->published_at)?->toAtomString() }}</updated>
            <category term="{{ $note->tag->slug }}" label="{{ $note->tag->name }}" />
            <content type="html">{{ $markdown->toFeedHtml($note->body) }}</content>
        </entry>
    @endforeach
</feed>
