{{--
    Layout of the admin panel (full-page Livewire components use it through
    #[Layout('layouts::admin')]). A sidebar on large screens, a drawer on phones.
--}}
@php
    use App\Enums\Section;
@endphp

<!DOCTYPE html>
<html lang="tr" data-signed-in="true">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ filled($title ?? null) ? $title.' · Admin' : 'Admin' }} · kadir.gulec.tr</title>

        {{--
            Runs before the first paint so dark mode never flashes white. wire:navigate
            copies the new page's <html> attributes, dropping "dark", so apply it again on every swap.
        --}}
        <script>
            (() => {
                const apply = () => {
                    let stored = null;
                    try { stored = localStorage.getItem('theme'); } catch (e) {}
                    const isDark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', isDark);
                };
                apply();
                document.addEventListener('livewire:navigating', (event) => event.detail.onSwap(apply));
            })();
        </script>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <meta name="theme-color" content="#26386b">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <x-pwa-head />

        @fonts(['nunito-sans', 'jetbrains-mono', 'fraunces'])

        @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    </head>
    <body class="min-h-dvh" x-data="{ menuOpen: false }" x-on:keydown.escape.window="menuOpen = false">
        <a href="#admin-icerik" class="sr-only rounded-lg bg-white px-3 py-2 focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70]">İçeriğe geç</a>

        {{-- Phone top bar --}}
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-zinc-200 bg-zinc-50/90 px-4 py-2.5 backdrop-blur lg:hidden dark:border-zinc-800 dark:bg-zinc-950/90">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2">
                <x-site.logo class="size-7 -rotate-12 text-accent" />
                <span class="font-display text-base font-semibold">arka ofis</span>
            </a>
            <x-admin.button variant="ghost" square icon="menu" x-on:click="menuOpen = true" aria-label="Menüyü aç" />
        </header>

        {{-- Drawer backdrop on phones --}}
        <div x-cloak x-show="menuOpen" x-transition.opacity x-on:click="menuOpen = false" class="fixed inset-0 z-40 bg-zinc-950/40 lg:hidden"></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col gap-6 overflow-y-auto border-r border-zinc-200 bg-zinc-100 p-4 transition-transform lg:translate-x-0 dark:border-zinc-800 dark:bg-zinc-900"
            x-bind:class="menuOpen && 'translate-x-0 shadow-2xl'"
            x-trap.inert="menuOpen && window.innerWidth < 1024"
            aria-label="Admin menüsü"
        >
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2.5 px-1">
                    <x-site.logo class="size-9 -rotate-12 text-accent" />
                    <span class="leading-tight">
                        <span class="block font-display text-lg font-semibold">arka ofis</span>
                        <span class="block font-mono text-[11px] text-zinc-500">kadir.gulec.tr</span>
                    </span>
                </a>
                <x-admin.button variant="ghost" size="sm" square icon="x" class="lg:hidden" x-on:click="menuOpen = false" aria-label="Menüyü kapat" />
            </div>

            <nav class="flex flex-1 flex-col gap-5 text-sm">
                <div class="space-y-0.5">
                    <x-admin.nav-item :href="route('admin.dashboard')" icon="layout-dashboard" :current="request()->routeIs('admin.dashboard')">Pano</x-admin.nav-item>
                </div>

                @php
                    $contentLinks = [
                        ['admin.posts.index', 'Yazılar', 'file-text', Section::Posts, 'admin.posts.*'],
                        ['admin.watched.index', 'İzlediklerim', 'clapperboard', Section::Watched, 'admin.watched.*'],
                        ['admin.goals.index', 'Hedefler', 'target', Section::Goals, 'admin.goals.*'],
                        ['admin.projects.index', 'Projeler', 'folder-git-2', Section::Projects, 'admin.projects.*'],
                    ];
                    $memberLinks = [
                        ['admin.users.index', 'Kullanıcılar', 'users', 'admin.users.*'],
                        ['admin.roles.index', 'Roller', 'shield', 'admin.roles.*'],
                        ['admin.comments.index', 'Yorumlar', 'message-square', 'admin.comments.*'],
                    ];
                    $contentLinks = array_filter($contentLinks, fn (array $link): bool => Route::has($link[0]));
                    $memberLinks = array_filter($memberLinks, fn (array $link): bool => Route::has($link[0]));
                @endphp

                @if ($contentLinks !== [])
                    <div class="space-y-0.5">
                        <p class="px-2.5 pb-1 text-xs font-bold tracking-wide text-zinc-500 uppercase">İçerik</p>
                        @foreach ($contentLinks as [$routeName, $label, $icon, $section, $pattern])
                            <x-admin.nav-item :href="route($routeName)" :icon="$icon" :dot="$section->adminDotClass()" :current="request()->routeIs($pattern)">{{ $label }}</x-admin.nav-item>
                        @endforeach
                    </div>
                @endif

                @if ($memberLinks !== [])
                    <div class="space-y-0.5">
                        <p class="px-2.5 pb-1 text-xs font-bold tracking-wide text-zinc-500 uppercase">Üyeler</p>
                        @foreach ($memberLinks as [$routeName, $label, $icon, $pattern])
                            <x-admin.nav-item :href="route($routeName)" :icon="$icon" :current="request()->routeIs($pattern)">{{ $label }}</x-admin.nav-item>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-0.5">
                    @can(\App\Enums\Permission::ReadMessages->value)
                        <x-admin.nav-item :href="route('admin.messages.index')" icon="mail" :count="\App\Models\ContactMessage::unreadCount()" :current="request()->routeIs('admin.messages.*')">Mesajlar</x-admin.nav-item>
                    @endcan
                    @if (Route::has('admin.backups.index'))
                        <x-admin.nav-item :href="route('admin.backups.index')" icon="archive" :current="request()->routeIs('admin.backups.*')">Yedekler</x-admin.nav-item>
                    @endif
                    @if (Route::has('admin.styleguide'))
                        <x-admin.nav-item :href="route('admin.styleguide')" icon="layers" :current="request()->routeIs('admin.styleguide')">Stil rehberi</x-admin.nav-item>
                    @endif
                    <x-admin.nav-item :href="route('home')" icon="globe" external>Siteyi gör</x-admin.nav-item>
                </div>
            </nav>

            <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <div x-data="themeSwitch" class="grid grid-cols-3 gap-1 rounded-lg bg-zinc-200/70 p-1 dark:bg-zinc-800" role="radiogroup" aria-label="Tema">
                    @foreach (['light' => ['sun', 'Açık'], 'dark' => ['moon', 'Koyu'], 'system' => ['monitor', 'Sistem']] as $theme => [$icon, $themeLabel])
                        <button
                            type="button"
                            role="radio"
                            x-on:click="set('{{ $theme }}')"
                            x-bind:aria-checked="theme === '{{ $theme }}'"
                            x-bind:class="theme === '{{ $theme }}' ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                            class="flex cursor-pointer items-center justify-center gap-1 rounded-md py-1 text-xs font-semibold"
                        >
                            <x-admin.icon :name="$icon" class="size-3.5" />
                            <span>{{ $themeLabel }}</span>
                        </button>
                    @endforeach
                </div>

                <x-admin.dropdown position="top" align="start" class="w-full">
                    <x-slot:trigger>
                        <button type="button" class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-left hover:bg-zinc-200/60 dark:hover:bg-zinc-800">
                            <span class="grid size-8 place-items-center rounded-full bg-accent text-xs font-bold text-accent-on">{{ auth()->user()->initials() }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span>
                                <span class="block truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
                            </span>
                            <x-admin.icon name="chevrons-up-down" class="text-zinc-400" />
                        </button>
                    </x-slot:trigger>

                    <x-admin.dropdown.item :href="route('profile.edit')" icon="user">Profil</x-admin.dropdown.item>
                    <x-admin.dropdown.item :href="route('security.edit')" icon="shield-check">Güvenlik</x-admin.dropdown.item>
                    <x-admin.dropdown.separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-admin.dropdown.item type="submit" icon="log-out" variant="danger">Çıkış yap</x-admin.dropdown.item>
                    </form>
                </x-admin.dropdown>
            </div>
        </aside>

        <main id="admin-icerik" class="px-4 py-6 sm:px-6 lg:ml-64 lg:px-10 lg:py-10">
            <div class="mx-auto max-w-6xl">
                {{ $slot }}
            </div>
        </main>

        <x-admin.toasts />
    </body>
</html>
