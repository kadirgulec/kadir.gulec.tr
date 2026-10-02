{{--
    Sign-in pages (Fortify views): a taped note in the middle of a notebook page.
    These pages are plain Blade, so Livewire's scripts (and with them Alpine)
    are added by hand for the passkey button and the password toggle.
--}}
@props([
    'title' => null,
    'heading' => null,
    'description' => null,
])

<x-layouts::site :section="\App\Enums\Section::Home" :title="$title">
    <div class="mx-auto max-w-md py-4 sm:py-10">
        <x-site.note tape="center" class="space-y-6 !p-6 !pt-9 sm:!p-9 sm:!pt-11">
            @if ($heading)
                <header class="space-y-2">
                    <h1 class="font-display text-3xl font-extrabold tracking-tight">{{ $heading }}</h1>
                    @if ($description)
                        <p class="text-ink-soft">{{ $description }}</p>
                    @endif
                </header>
            @endif

            <x-site.form.status :message="session('status')" />

            {{ $slot }}
        </x-site.note>
    </div>

    @livewireScripts
</x-layouts::site>
