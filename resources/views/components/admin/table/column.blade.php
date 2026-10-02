{{--
    A column header. With "sortable", the header becomes a button; pass wire:click
    for the sort action and "sorted" / "direction" (asc | desc) for the current state.
--}}
@props([
    'sortable' => false,
    'sorted' => false,
    'direction' => 'asc',
    'align' => 'start',
])

<th
    scope="col"
    @if ($sortable) aria-sort="{{ $sorted ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif
    {{ $attributes->whereDoesntStartWith('wire:')->class(['px-4 py-2.5 whitespace-nowrap', 'text-right' => $align === 'end']) }}
>
    @if ($sortable)
        <button type="button" {{ $attributes->whereStartsWith('wire:') }} class="-mx-1 inline-flex cursor-pointer items-center gap-1 rounded px-1 uppercase hover:text-zinc-900 dark:hover:text-white">
            {{ $slot }}
            <x-admin.icon :name="$sorted ? ($direction === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevrons-up-down'" class="size-3.5 {{ $sorted ? '' : 'opacity-50' }}" />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
