@use('App\Enums\Section')

@php
    $description = match ($section) {
        Section::Home => 'Defterin kapağı: son yazı, son izlenen film, aktif zincirler ve öne çıkan proje burada toplanacak.',
        Section::Posts => 'Fihrist düzeninde yazılar, öne çıkan defter girdileri ve washi tape etiketler.',
        Section::Watched => 'Afiş şeridi, şu an izlediklerim ve tarihli izleme günlüğü.',
        Section::Goals => 'Zincirler, bu yılın hedefleri ve uzun vade panosu.',
        Section::Projects => 'Projeler, vaka çalışmaları ve geliştirme günlükleri.',
        Section::About => 'Ankara\'dan Düren\'e uzanan hikâye, alet çantası ve iletişim.',
    };
@endphp

<x-layouts::site :section="$section" :title="$section === Section::Home ? null : $section->label()">
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">{{ now()->locale('tr')->translatedFormat('j F Y') }}</p>

    <h1 class="relative mt-3 inline-block font-display text-5xl font-extrabold tracking-tight sm:text-6xl">
        {{ $section->label() }}
        <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
    </h1>

    <div class="relative mt-14 max-w-md -rotate-1 rounded-sm bg-paper-deep p-6 pt-8 shadow-[0_8px_20px_-10px_rgb(60_40_20/0.35)] dark:shadow-[0_8px_20px_-8px_rgb(0_0_0/0.8)]">
        <span class="tape -top-3 left-1/2 -translate-x-1/2 -rotate-3"></span>
        <p class="font-hand text-3xl font-bold text-section-ink">Bu sayfa yakında doluyor…</p>
        <p class="mt-2 text-ink-soft">{{ $description }}</p>
    </div>
</x-layouts::site>
