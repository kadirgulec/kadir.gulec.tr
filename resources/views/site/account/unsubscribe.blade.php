@use('App\Enums\Section')

<x-layouts::site :section="Section::Home" :title="$heading">
    <div class="mx-auto max-w-md py-10">
        <x-site.note tape="center" class="space-y-5 !p-8 !pt-11">
            <h1 class="font-display text-3xl font-extrabold">{{ $heading }}</h1>

            @if ($done)
                <x-site.form.status :message="$doneText" />
            @else
                <p class="text-ink-soft">{{ $question }}</p>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    <x-site.form.button type="submit">Evet, bırak</x-site.form.button>
                </form>
            @endif
        </x-site.note>
    </div>
</x-layouts::site>
