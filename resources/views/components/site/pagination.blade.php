@props(['paginator'])

{{-- Notebook-style page links for a LengthAwarePaginator: ← önceki · 2 / 5 · sonraki → --}}
@if ($paginator->hasPages())
    <nav aria-label="Sayfalar" {{ $attributes->class('flex items-center justify-between gap-4 border-t-2 border-dashed border-rule pt-6 font-hand text-xl') }}>
        @if ($paginator->onFirstPage())
            <span class="text-ink-faint/60" aria-hidden="true">← daha yeni</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="text-section-ink underline decoration-section decoration-2 underline-offset-4 hover:text-ink">← daha yeni</a>
        @endif

        <p class="font-mono text-xs text-ink-faint">sayfa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</p>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="text-section-ink underline decoration-section decoration-2 underline-offset-4 hover:text-ink">daha eski →</a>
        @else
            <span class="text-ink-faint/60" aria-hidden="true">daha eski →</span>
        @endif
    </nav>
@endif
