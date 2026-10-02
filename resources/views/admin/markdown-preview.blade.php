{{-- The document inside the Markdown editor's preview iframe. --}}
<!DOCTYPE html>
<html lang="tr" @class(['dark' => $dark])>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="robots" content="noindex" />
        @fonts(['fraunces', 'nunito-sans', 'caveat', 'jetbrains-mono'])
        @vite(['resources/css/site.css'])
    </head>
    <body data-section="{{ $section->value }}" class="paper min-h-dvh p-6 text-ink sm:p-10">
        <div class="prose-notebook max-w-2xl xl:max-w-[34rem]">
            @if (trim((string) $html) === '')
                <p class="font-hand text-2xl text-ink-faint">Önizlenecek bir şey yok.</p>
            @else
                {{ $html }}
            @endif
        </div>
    </body>
</html>
