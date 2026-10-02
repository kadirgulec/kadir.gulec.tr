{{--
    Toast stack of the admin layout. Shows every browser "toast" event and the
    "toast" flash of the previous request (after a redirect):
    $this->dispatch('toast', text: 'Kaydedildi.', variant: 'success');
    session()->flash('toast', ['text' => 'Kaydedildi.', 'variant' => 'success']);
    variant: success | danger | info
--}}
<div
    x-data="{
        items: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.items.push({ id, variant: 'success', ...toast });
            setTimeout(() => this.remove(id), toast.variant === 'danger' ? 8000 : 4000);
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    }"
    x-init="@if (session()->has('toast')) add(@js(session('toast'))) @endif"
    x-on:toast.window="add($event.detail)"
    aria-live="polite"
    class="pointer-events-none fixed right-4 bottom-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2"
>
    <template x-for="item in items" :key="item.id">
        <div
            x-transition.opacity
            class="pointer-events-auto flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-3.5 text-sm shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        >
            <span
                class="mt-0.5 size-2.5 shrink-0 rounded-full"
                :class="{
                    'bg-green-500': item.variant === 'success',
                    'bg-red-500': item.variant === 'danger',
                    'bg-sky-500': item.variant === 'info',
                }"
                aria-hidden="true"
            ></span>
            <p class="flex-1 font-semibold text-zinc-800 dark:text-zinc-100" x-text="item.text"></p>
            <button type="button" x-on:click="remove(item.id)" class="cursor-pointer text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200" aria-label="Kapat">
                <x-admin.icon name="x" />
            </button>
        </div>
    </template>
</div>
