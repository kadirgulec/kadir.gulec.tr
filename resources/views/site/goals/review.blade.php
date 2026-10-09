@use('App\Enums\Section')

{{--
    "Her ayın sonunda dürüst bir bakış": one month's review. $review is null
    before the first one is published (the page then explains what will come).
--}}
@php
    $cardShadow = 'shadow-[0_10px_22px_-14px_rgb(60_40_20/0.5)] dark:shadow-[0_10px_22px_-10px_rgb(0_0_0/0.85)]';
    $columns = $review === null ? [] : [
        ['kind' => 'good', 'sign' => '+', 'title' => 'İyi giden', 'empty' => 'Bu ay buraya bir şey yazmadım.'],
        ['kind' => 'hard', 'sign' => '–', 'title' => 'Zorlandığım', 'empty' => 'Bu ay buraya bir şey yazmadım.'],
        ['kind' => 'try', 'sign' => '→', 'title' => $review['nextMonthIn'].' deneyeceğim', 'empty' => 'Gelecek ay için yeni bir deneme yok.'],
    ];
@endphp

<x-layouts::site
    :section="Section::Goals"
    :title="$review ? $review['monthName'].' değerlendirmesi' : 'Aylık değerlendirme'"
    :draft="$review['isDraft'] ?? false"
    :description="$review ? ($review['summary'] ?? $review['monthName'].': ne iyi gitti, nerede zorlandım, gelecek ay ne deneyeceğim.') : 'Her ayın sonunda dürüst bir bakış: ne iyi gitti, nerede zorlandım, gelecek ay neyi değiştireceğim.'"
>
    <p class="font-mono text-xs tracking-widest text-ink-faint uppercase">
        <a href="{{ route('goals.index') }}" class="hover:text-section-ink">Hedefler</a> · aylık değerlendirme
    </p>

    <div class="mt-3 grid items-end gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <h1 class="font-display text-5xl leading-[1.05] font-extrabold tracking-tight sm:text-6xl">
            Her ayın sonunda
            <span class="relative inline-block">
                dürüst bir bakış.
                <x-site.scribble class="absolute -bottom-3 left-0 h-3.5 w-full text-section" />
            </span>
        </h1>
        <p class="text-lg leading-relaxed text-ink-soft">Ne iyi gitti, nerede zorlandım, gelecek ay neyi değiştireceğim. Rakamlar sitedeki kayıtlardan otomatik geliyor.</p>
    </div>

    @if ($review === null)
        <p class="mt-16 font-hand text-2xl text-section-ink">İlk değerlendirme ayın sonunda burada olacak.</p>
        <p class="mt-2 text-ink-soft">
            Zincirler, hedefler ve yazılar devam ediyor; şimdilik <a href="{{ route('goals.index') }}" class="underline decoration-dotted underline-offset-4 hover:text-section-ink">hedeflerin kendisine</a> bakabilirsin.
        </p>
    @else
        {{-- Month tabs --}}
        <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-y border-dashed border-ink/15 py-4">
            <nav class="flex flex-wrap items-center gap-3" aria-label="Aylar">
                @foreach ($review['tabs'] as $tab)
                    <a
                        href="{{ $tab['url'] }}"
                        @if ($tab['current']) aria-current="page" @endif
                        @class([
                            'washi [--tape-color:var(--color-section)]',
                            'rotate-[-1deg]' => $loop->odd,
                            'rotate-[1deg]' => $loop->even,
                            'bg-section! font-semibold text-section-on!' => $tab['current'],
                        ])
                    >{{ $tab['label'] }}</a>
                @endforeach
            </nav>
            <p class="font-mono text-xs text-ink-faint">sıradaki değerlendirme: {{ $review['nextReviewOn'] }}</p>
        </div>

        {{-- The month --}}
        <section class="mt-10" aria-labelledby="ay">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="min-w-0">
                    <h2 id="ay" class="font-display text-4xl font-semibold sm:text-5xl">{{ $review['monthName'] }}</h2>
                    @if ($review['summary'])
                        <p class="mt-3 max-w-2xl text-xl leading-snug text-ink-soft">{{ $review['summary'] }}</p>
                    @endif
                </div>

                @if ($review['score'] !== null)
                    <p class="flex shrink-0 items-center gap-3">
                        <x-site.grade :value="$review['score']" size="lg" />
                        <span class="font-hand text-xl leading-tight text-ink-soft" aria-hidden="true">/10<br>ayın puanı</span>
                    </p>
                @endif
            </div>

            {{-- Numbers --}}
            @if ($review['tiles'])
                <dl class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($review['tiles'] as $tile)
                        <div class="relative flex min-w-0 flex-col rounded-sm bg-paper-deep p-4 {{ $cardShadow }}">
                            <dt class="order-2 mt-1 min-w-0 text-sm leading-snug text-ink-soft">
                                @if ($tile['unit'])
                                    <span class="font-semibold text-ink">{{ $tile['unit'] }}</span>
                                @endif
                                @if ($tile['label'])
                                    <span class="line-clamp-2" title="{{ $tile['label'] }}">{{ $tile['label'] }}</span>
                                @elseif ($tile['labelLength'])
                                    <span class="block"><span aria-hidden="true">🔒</span> <x-site.censored :length="$tile['labelLength']" label="sansürlü hedef" /></span>
                                @endif
                                @if ($tile['note'])
                                    <span class="mt-1 block font-mono text-[11px] text-ink-faint">{{ $tile['note'] }}</span>
                                @endif
                            </dt>
                            <dd class="order-1 font-display text-4xl leading-none font-extrabold text-section-ink">
                                @if ($tile['url'])
                                    <a href="{{ $tile['url'] }}" class="after:absolute after:inset-0 hover:underline hover:decoration-2 hover:underline-offset-4">{{ $tile['value'] }}</a>
                                @else
                                    {{ $tile['value'] }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if ($review['topPost'])
                <p class="mt-5 font-hand text-xl text-ink-soft">
                    ayın en çok okunan yazısı:
                    <a href="{{ $review['topPost']['url'] }}" class="text-section-ink underline decoration-dotted underline-offset-4 hover:text-ink">{{ $review['topPost']['title'] }}</a>
                    <span class="font-mono text-xs text-ink-faint">({{ $review['topPost']['views'] }} görüntüleme)</span>
                </p>
            @endif
        </section>

        {{-- Good, hard, next --}}
        <div class="mt-14 grid gap-10 md:grid-cols-3 md:gap-8">
            @foreach ($columns as $column)
                <section aria-labelledby="sutun-{{ $column['kind'] }}" class="min-w-0">
                    <h2 id="sutun-{{ $column['kind'] }}" class="flex items-baseline gap-3 border-b border-ink/15 pb-3 font-display text-xl font-semibold">
                        <span class="w-4 shrink-0 font-hand text-2xl leading-none text-section-ink" aria-hidden="true">{{ $column['sign'] }}</span>
                        {{ $column['title'] }}
                    </h2>

                    @if ($review['items'][$column['kind']])
                        <ul class="divide-y divide-dashed divide-ink/15">
                            @foreach ($review['items'][$column['kind']] as $item)
                                <li class="py-3 leading-relaxed [&_a]:underline [&_a]:decoration-dotted [&_a]:underline-offset-4 [&_p]:m-0">{{ $item['html'] }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="py-3 font-hand text-lg text-ink-faint">{{ $column['empty'] }}</p>
                    @endif
                </section>
            @endforeach
        </div>

        {{-- Last month's "try" items, kept honestly --}}
        @if ($review['previousTries'])
            <section class="mt-14" aria-labelledby="denediklerim">
                <h2 id="denediklerim" class="font-display text-2xl font-semibold">{{ $review['monthIn'] }} denediklerim</h2>
                <p class="font-hand text-lg text-ink-faint">geçen ay "deneyeceğim" dediklerim; tutmayanlar silinmez, üstleri karalanır</p>

                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($review['previousTries'] as $try)
                        <li class="flex items-start gap-3">
                            <x-site.checkbox :checked="$try['outcome'] === 'done'" class="mt-0.5 size-5 text-ink-faint" />
                            <span @class(['min-w-0 leading-relaxed [&_p]:m-0', 'scribbled-out text-ink-faint' => $try['outcome'] === 'not-done'])>{{ $try['html'] }}</span>
                            @if ($try['outcome'] === 'not-done')
                                <span class="shrink-0 font-hand text-lg text-ink-faint">olmadı</span>
                            @elseif ($try['outcome'] === null)
                                <span class="sr-only">(henüz işaretlenmedi)</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Neighbours and archive --}}
        <nav class="mt-16 flex flex-wrap justify-between gap-4 border-t border-dashed border-ink/15 pt-6 font-hand text-xl" aria-label="Önceki ve sonraki ay">
            @if ($review['older'])
                <a href="{{ $review['older']['url'] }}" class="text-ink-soft hover:text-section-ink">← {{ $review['older']['label'] }}</a>
            @else
                <span></span>
            @endif
            @if ($review['newer'])
                <a href="{{ $review['newer']['url'] }}" class="text-ink-soft hover:text-section-ink">{{ $review['newer']['label'] }} →</a>
            @endif
        </nav>

        @if (count($review['archive']) > 0 && collect($review['archive'])->flatten(1)->count() > count($review['tabs']))
            <section class="mt-10" aria-labelledby="arsiv">
                <h2 id="arsiv" class="font-display text-xl font-semibold">Bütün aylar</h2>
                @foreach ($review['archive'] as $year => $months)
                    <div class="mt-3 grid gap-x-6 sm:grid-cols-[4rem_1fr]">
                        <p class="font-mono text-sm font-semibold text-ink-soft">{{ $year }}</p>
                        <ul class="flex flex-wrap gap-x-5 gap-y-1">
                            @foreach ($months as $month)
                                <li>
                                    <a href="{{ $month['url'] }}" @if ($month['current']) aria-current="page" @endif @class(['hover:text-section-ink', 'font-semibold text-section-ink' => $month['current']])>
                                        {{ $month['label'] }}@if ($month['score'] !== null) <span class="font-mono text-xs text-ink-faint">{{ $month['score'] }}/10</span>@endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </section>
        @endif
    @endif
</x-layouts::site>
