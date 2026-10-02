{{--
    Multi-select with free entry, entangled with a Livewire array of names:
    <x-admin.combobox wire:model="technologyNames" :options="$allNames" label="Teknolojiler" />
    Type to filter, Enter adds (or creates), Backspace removes the last one.
--}}
@props([
    'label' => null,
    'description' => null,
    'help' => null,
    'options' => [],
    'placeholder' => 'Yaz ve Enter\'a bas…',
    'allowCreate' => true,
])

@php
    $field = \App\Support\FormControl::name($attributes);
    $id = \App\Support\FormControl::id($attributes, $field);
    $error = $field !== null ? ($errors->first($field) ?: $errors->first($field.'.*')) : null;
@endphp

<x-admin.field :label="$label" :description="$description" :help="$help" :error="$error" :for="$id" {{ $attributes->only('class') }}>
    <div
        x-data="combobox({ selected: $wire.$entangle(@js($field)), options: @js(array_values($options)), allowCreate: @js($allowCreate) })"
        x-on:click.outside="open = false"
        class="relative"
    >
        <div
            x-on:click="$refs.input.focus()"
            @class([
                'control flex min-h-10 cursor-text flex-wrap items-center gap-1.5 !py-1.5 focus-within:border-accent focus-within:ring-2 focus-within:ring-accent/20',
                '!border-red-500' => $error,
            ])
        >
            <template x-for="item in selected" :key="item">
                <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 py-0.5 pr-1 pl-2 text-xs font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">
                    <span x-text="item"></span>
                    <button type="button" x-on:click.stop="remove(item)" class="grid size-4 cursor-pointer place-items-center rounded text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:hover:bg-zinc-700" x-bind:aria-label="item + ' kaldır'">
                        <x-admin.icon name="x" class="size-3" />
                    </button>
                </span>
            </template>

            <input
                x-ref="input"
                id="{{ $id }}"
                type="text"
                role="combobox"
                aria-autocomplete="list"
                x-bind:aria-expanded="open"
                aria-controls="{{ $id }}-listbox"
                x-model="query"
                x-on:focus="open = true"
                x-on:input="open = true; active = 0"
                x-on:keydown.enter.prevent="enter()"
                x-on:keydown.backspace="backspace()"
                x-on:keydown.arrow-down.prevent="move(1)"
                x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.escape="open = false"
                x-on:keydown.tab="open = false"
                placeholder="{{ $placeholder }}"
                class="min-w-32 flex-1 bg-transparent py-0.5 text-sm outline-none placeholder:text-zinc-400"
            />
        </div>

        <ul
            x-cloak
            x-show="open && (matches.length > 0 || canCreate)"
            id="{{ $id }}-listbox"
            role="listbox"
            class="absolute z-40 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-zinc-200 bg-white p-1 text-sm shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        >
            <template x-for="(option, index) in matches" :key="option">
                <li
                    role="option"
                    x-on:mousedown.prevent="add(option)"
                    x-on:mouseenter="active = index"
                    x-bind:aria-selected="active === index"
                    x-bind:class="active === index ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                    class="cursor-pointer rounded-md px-2.5 py-1.5"
                    x-text="option"
                ></li>
            </template>
            <li
                x-show="canCreate"
                role="option"
                x-on:mousedown.prevent="add(query)"
                x-bind:aria-selected="active === matches.length"
                x-bind:class="active === matches.length ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                class="cursor-pointer rounded-md px-2.5 py-1.5 font-semibold text-accent"
            >
                Yeni: <span x-text="query.trim()"></span>
            </li>
        </ul>
    </div>
</x-admin.field>
