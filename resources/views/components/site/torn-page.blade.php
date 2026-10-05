@props([
    'code',
    'heading',
])

{{-- A page torn out of the notebook, taped back in: the body of every error page. --}}
<div {{ $attributes->class('mx-auto max-w-xl') }}>
    <div class="torn-edge relative rotate-[-1deg] rounded-t-sm bg-paper-deep px-6 pt-10 pb-16 shadow-[0_10px_22px_-12px_rgb(60_40_20/0.5)] sm:px-10 dark:shadow-[0_10px_22px_-10px_rgb(0_0_0/0.85)]">
        <span class="tape -top-3 left-1/2 -translate-x-1/2 -rotate-3"></span>
        <span class="pointer-events-none absolute top-5 right-6 font-display text-7xl font-extrabold text-ink/10 select-none" aria-hidden="true">{{ $code }}</span>

        <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">Hata {{ $code }}</p>
        <h1 class="mt-3 font-hand text-4xl leading-tight text-ink sm:text-5xl">{{ $heading }}</h1>

        <div class="mt-5 space-y-3 text-lg leading-relaxed text-ink-soft">
            {{ $slot }}
        </div>
    </div>

    @isset($after)
        <div class="mt-10 px-2">{{ $after }}</div>
    @endisset
</div>
