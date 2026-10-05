{{-- Plain text: printed as is, HTML escaping would turn quotes into entities. --}}
Zinciri kırma

@foreach ($lines as $line)
- {!! $line['title'] !!} ({!! $line['cadence'] !!}): {!! $line['text'] !!}
@endforeach

--
Mazeretli günler de sayılır. Bu hatırlatma sadece sana gider, takipçiler görmez.
Panoda işaretle: {!! $dashboardUrl !!}
