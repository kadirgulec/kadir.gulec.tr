{{--
    Data table. Compose it from x-admin.table.columns / column and
    x-admin.table.rows / row / cell; pass a paginator to get page links below.
--}}
@props([
    'paginate' => null,
])

<div {{ $attributes->class('overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900') }}>
    <div class="relative overflow-x-auto">
        <table class="w-full text-left text-sm">
            {{ $slot }}
        </table>
    </div>

    @if ($paginate && $paginate->hasPages())
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
            {{ $paginate->links('components.admin.pagination') }}
        </div>
    @endif
</div>
