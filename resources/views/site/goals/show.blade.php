@use('App\Enums\Section')

<x-layouts::site :section="Section::Goals" :title="$goal['title']">
    <a href="{{ route('goals.index') }}#uzun-vade" class="font-hand text-xl text-ink-soft hover:text-section-ink">← Uzun vade</a>

    <p class="mt-8 font-mono text-xs tracking-widest text-ink-faint uppercase">uzun vadeli hedef @if ($goal['since']) · başlangıç: {{ $goal['since'] }} @endif</p>

    <h1 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-extrabold tracking-tight text-balance sm:text-5xl">{{ $goal['title'] }}</h1>

    <x-site.scribble class="mt-3 h-3.5 w-40 text-section" />

    {{-- Why it matters, pinned like on the board --}}
    <div class="relative mt-10 max-w-xl rotate-[-1deg] bg-paper-deep p-6 pt-8 shadow-[2px_10px_18px_-10px_rgb(60_40_20/0.5)] dark:shadow-[2px_10px_18px_-8px_rgb(0_0_0/0.9)]">
        <x-site.push-pin class="absolute -top-3 left-1/2 -translate-x-1/2" />
        <p class="font-mono text-[11px] tracking-wider text-ink-faint uppercase">neden önemli?</p>
        <div class="mt-2 font-hand text-2xl leading-snug [&_p+p]:mt-3">{{ $goal['why'] }}</div>
    </div>

    @if ($goal['imageUrl'])
        <img src="{{ $goal['imageUrl'] }}" alt="" class="mt-10 max-w-xl rotate-[1deg] bg-[#fffdf7] p-2.5 shadow-[0_8px_18px_-8px_rgb(60_40_20/0.5)] dark:bg-[#2e2a25]" loading="lazy" />
    @endif

    {{-- Small steps serving this goal --}}
    <section class="mt-16" aria-labelledby="bagli-hedefler">
        <h2 id="bagli-hedefler" class="font-display text-2xl font-semibold">Bu hedefe hizmet edenler</h2>
        <p class="font-hand text-lg text-ink-faint">büyük hedef → bu yılın hedefleri → her günün zincirleri</p>

        @if ($yearlyGoals || $chains)
            <ul class="mt-6 flex flex-col gap-5">
                @foreach ($yearlyGoals as $yearly)
                    <li class="rounded-sm bg-paper-deep p-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-3">
                            <a href="{{ route('goals.index') }}#hedef-{{ $yearly['slug'] }}" class="font-display text-lg font-semibold hover:text-section-ink">
                                @if ($yearly['title'])
                                    {{ $yearly['title'] }}
                                @else
                                    <x-site.censored :length="$yearly['titleLength']" />
                                @endif
                            </a>
                            <span class="font-mono text-xs text-ink-faint">bu yıl</span>
                        </div>

                        @switch($yearly['type'])
                            @case('numeric')
                                <div class="mt-3 flex items-center gap-4">
                                    <x-site.pencil-progress :value="$yearly['current']" :max="$yearly['target']" :marker="$yearShare" :label="($yearly['title'] ?? 'Hedef').' ilerlemesi'" class="mb-5 flex-1" />
                                    <span class="mb-5 shrink-0 font-mono text-sm">{{ $yearly['current'] }} / {{ $yearly['target'] }}</span>
                                </div>
                                @break
                            @case('milestones')
                                <p class="mt-2 font-mono text-sm text-ink-soft">{{ collect($yearly['milestones'])->where('done', true)->count() }}/{{ count($yearly['milestones']) }} kilometre taşı</p>
                                @break
                            @default
                                <p class="mt-2 font-hand text-xl text-ink-soft">{{ $yearly['achievedAt'] ? 'başarıldı ✓' : 'henüz değil' }}</p>
                        @endswitch

                        @foreach ($yearly['chains'] as $chain)
                            <div class="mt-4 border-t border-dashed border-rule pt-3 pl-4">
                                <div class="flex items-baseline justify-between gap-3">
                                    <a href="{{ route('goals.chain', $chain['slug']) }}" class="hover:text-section-ink">↳ {{ $chain['title'] ?? 'sansürlü zincir' }}</a>
                                    <span class="font-mono text-sm text-section-ink">🔥 {{ $chain['streak'] }}</span>
                                </div>
                                <x-site.chain :days="$chain['days']" class="mt-2" />
                            </div>
                        @endforeach
                    </li>
                @endforeach

                @foreach ($chains as $chain)
                    <li class="rounded-sm bg-paper-deep p-5">
                        <div class="flex items-baseline justify-between gap-3">
                            <a href="{{ route('goals.chain', $chain['slug']) }}" class="font-display text-lg font-semibold hover:text-section-ink">{{ $chain['title'] ?? 'sansürlü zincir' }}</a>
                            <span class="font-mono text-sm text-section-ink">🔥 {{ $chain['streak'] }} gün</span>
                        </div>
                        <p class="font-mono text-xs text-ink-faint">her gün</p>
                        <x-site.chain :days="$chain['days']" class="mt-2" />
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-6 font-hand text-2xl text-section-ink">Henüz bu hedefe bağlı küçük bir adım yok.</p>
        @endif
    </section>

    {{-- The story so far --}}
    @if ($goal['updates'])
        <section class="mt-16 max-w-2xl" aria-labelledby="guncellemeler">
            <h2 id="guncellemeler" class="font-display text-2xl font-semibold">Hikâyesi</h2>
            <p class="font-hand text-lg text-ink-faint">en yenisi en üstte</p>

            <x-site.logbook :entries="$goal['updates']" class="mt-6" />
        </section>
    @endif
</x-layouts::site>
