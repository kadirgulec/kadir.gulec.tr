{{--
    Live length of a Livewire text property, read in the browser as it is typed.
    Turns amber past "soft" and red past "hard"; it never blocks saving.
    <x-admin.char-counter field="form.body" :soft="250" :hard="500" />
--}}
@props([
    'field',
    'soft',
    'hard',
])

<p
    x-data="{ get count() { return [...($wire.$get(@js($field)) ?? '')].length } }"
    {{ $attributes->class('text-right font-mono text-xs') }}
    x-bind:class="count > {{ (int) $hard }} ? 'font-bold text-red-600 dark:text-red-400' : (count > {{ (int) $soft }} ? 'font-bold text-amber-600 dark:text-amber-400' : 'text-zinc-500')"
>
    <span x-text="count">0</span> / {{ $soft }}
    <span x-show="count > {{ (int) $hard }}" x-cloak>· bu artık bir yazı olabilir</span>
    <span x-show="count > {{ (int) $soft }} && count <= {{ (int) $hard }}" x-cloak>· not uzuyor</span>
</p>
