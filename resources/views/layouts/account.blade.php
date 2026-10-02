{{--
    "Hesabım" pages of members (and Kadir): a notebook page with a small
    table of contents on the left. Used by full-page Livewire components
    through #[Layout('layouts::account')].
--}}
@php
    $links = [
        ['profile.edit', 'Profil'],
        ['security.edit', 'Güvenlik'],
        ['follows.index', 'Takip ettiklerim'],
        ['notifications.edit', 'Bildirimler'],
    ];
@endphp

<x-layouts::site :section="\App\Enums\Section::Home" :title="$title ?? 'Hesabım'">
    <div class="grid gap-10 lg:grid-cols-[12rem_1fr]">
        <nav aria-label="Hesabım" class="space-y-4">
            <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">Hesabım</p>

            <ul class="flex flex-wrap gap-x-5 gap-y-2 lg:flex-col">
                @foreach ($links as [$routeName, $label])
                    @if (Route::has($routeName))
                        <li>
                            <a
                                href="{{ route($routeName) }}"
                                wire:navigate
                                @if (request()->routeIs($routeName)) aria-current="page" @endif
                                @class([
                                    'font-semibold underline-offset-[6px] hover:text-ink',
                                    'text-ink underline decoration-section decoration-[3px]' => request()->routeIs($routeName),
                                    'text-ink-soft' => ! request()->routeIs($routeName),
                                ])
                            >{{ $label }}</a>
                        </li>
                    @endif
                @endforeach

                @can(\App\Enums\Permission::AccessAdmin->value)
                    <li><a href="{{ route('admin.dashboard') }}" class="font-semibold text-ink-soft hover:text-ink">Arka ofis ↗</a></li>
                @endcan
            </ul>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-site.form.button type="submit" variant="link" data-test="logout-button">Çıkış yap</x-site.form.button>
            </form>
        </nav>

        <div class="max-w-2xl min-w-0 space-y-12">
            {{ $slot }}
        </div>
    </div>
</x-layouts::site>
