{{-- Page links for Livewire paginators, used by x-admin.table. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalar" class="flex items-center justify-between gap-3 text-sm">
        <p class="text-zinc-500 dark:text-zinc-400">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}
        </p>

        <div class="flex items-center gap-1">
            <x-admin.button size="sm" variant="ghost" square icon="chevron-left" wire:click="previousPage('{{ $paginator->getPageName() }}')" :disabled="$paginator->onFirstPage()" aria-label="Önceki sayfa" />

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-zinc-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <x-admin.button
                            size="sm"
                            square
                            :variant="$page === $paginator->currentPage() ? 'subtle' : 'ghost'"
                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                            wire:key="page-{{ $page }}"
                            :aria-current="$page === $paginator->currentPage() ? 'page' : null"
                        >{{ $page }}</x-admin.button>
                    @endforeach
                @endif
            @endforeach

            <x-admin.button size="sm" variant="ghost" square icon="chevron-right" wire:click="nextPage('{{ $paginator->getPageName() }}')" :disabled="! $paginator->hasMorePages()" aria-label="Sonraki sayfa" />
        </div>
    </nav>
@endif
