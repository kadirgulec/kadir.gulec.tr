@props([
    'title',
])

{{--
    The notebook page without menus, sessions or database: for the 500 and 503
    pages, which must render even when the rest of the app does not.
--}}
<!DOCTYPE html>
<html lang="tr">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />
        <title>{{ $title }} · Kadir Gülec</title>

        <script>
            (() => {
                let stored = null;
                try { stored = localStorage.getItem('theme'); } catch (e) {}
                const isDark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts(['fraunces', 'nunito-sans', 'caveat', 'jetbrains-mono'])

        @vite(['resources/css/site.css'])
    </head>
    <body data-section="home" class="min-h-dvh antialiased">
        <div class="mx-auto max-w-3xl px-2 py-6 sm:px-6 sm:py-10">
            <div class="paper relative rounded-[3px] px-6 py-10 shadow-[0_1px_2px_rgb(60_40_20/0.08),0_12px_32px_-12px_rgb(60_40_20/0.25)] sm:px-12 sm:py-14 dark:shadow-[0_1px_2px_rgb(0_0_0/0.5),0_16px_40px_-12px_rgb(0_0_0/0.7)]">
                <x-site.signature class="stamp mb-10 h-14 w-auto -rotate-6 text-home-ink" />

                <main>{{ $slot }}</main>
            </div>
        </div>
    </body>
</html>
