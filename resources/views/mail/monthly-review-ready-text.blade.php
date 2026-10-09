{{-- Plain text: printed as is, HTML escaping would turn quotes into entities. --}}
{!! $monthName !!} değerlendirmesinin taslağı hazır

Rakamlar donduruldu; ne iyi gitti, nerede zorlandın, gelecek ay ne deneyeceksin, gerisi sende.
@if ($suggestions !== [])

Rakamlardan öneriler:
@foreach ($suggestions as $suggestion)
{!! $suggestion['sign'] !!} {!! $suggestion['text'] !!}
@endforeach
@endif

--
Taslak sen yayınlayana kadar kimseye görünmez.
Değerlendirmeyi yaz: {!! $editUrl !!}
