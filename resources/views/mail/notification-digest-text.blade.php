Merhaba {{ $user->name }}, takip ettiklerinde olanlar:

@foreach ($entries as $entry)
- {{ $entry['item']->title }}
  {{ $entry['item']->url }}
@if ($entry['item']->body)
  {{ \Illuminate\Support\Str::limit($entry['item']->body, 280) }}
@endif
@if ($entry['unfollowUrl'])
  Takipten çık: {{ $entry['unfollowUrl'] }}
@endif

@endforeach
Sıklığı değiştir: {{ $settingsUrl }}
Hiç e-posta gönderme: {{ $unsubscribeUrl }}
