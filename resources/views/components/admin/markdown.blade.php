{{--
    Markdown editor: monospace textarea, toolbar, preview tab (rendered with
    the site's own CSS in an iframe) and the ⓘ syntax guide next to the label.
    <x-admin.markdown wire:model="body" label="Vaka çalışması" section="projects" />
    "reviews" adds the spoiler and quote buttons of film reviews. "upload" names a
    Livewire property for in-text images: the page stores the upload and
    dispatches 'markdown-insert' with the Markdown to put at the cursor.
    "inline" is for short notes: the guide and toolbar only offer inline marks
    (headings, lists, code blocks, side notes and images still render).
--}}
@props([
    'label' => null,
    'description' => null,
    'section' => 'home',
    'rows' => 16,
    'reviews' => false,
    'upload' => null,
    'inline' => false,
])

<div
    x-data="markdownEditor({ previewUrl: @js(route('admin.markdown.preview')), section: @js($section) })"
    x-on:markdown-insert.window="insert($event.detail.text)"
    {{ $attributes->only('class')->class('space-y-1.5') }}
>
    <x-admin.textarea mono :rows="$rows" :label="$label" :description="$description" {{ $attributes->except('class') }} x-ref="editorArea" x-show="tab === 'write'" control-class="rounded-t-none">
        <x-slot:help>
            <div class="space-y-1.5">
                <p class="font-bold">Kısa kılavuz</p>
                @if ($inline)
                <p><code>**kalın**</code> · <code>*eğik*</code> · <code>`kod`</code></p>
                <p><code>==metin==</code> fosforlu kalem</p>
                <p><code>[link](https://…)</code></p>
                <p class="opacity-75">Başlık, liste, kod bloğu, kenar notu ve görsel de çalışır ama post-it'i büyütür; kullanmamanı öneririm. O kadar uzunsa bir yazı olabilir.</p>
                @else
                <p><code>## Başlık</code> · <code>**kalın**</code> · <code>*eğik*</code></p>
                <p><code>==metin==</code> fosforlu kalem</p>
                <p><code>metin[^1]</code> + en alta <code>[^1]: not</code> → kenar notu</p>
                <p><code>[link](https://…)</code> · <code>- madde</code> · <code>1. madde</code></p>
                <p><code>`kod`</code> · <code>```php</code> … <code>```</code> kod bloğu</p>
                <p><code>![ne görünüyor](adres "altyazı")</code> tek satırda: polaroid görsel</p>
                @if ($reviews)
                    <p><code>:::spoiler</code> … <code>:::</code> spoiler (markörle kapalı)</p>
                    <p><code>:::replik Kişi</code> … <code>:::</code> replik post-it'i</p>
                @endif
                @endif
                <p class="opacity-75">HTML yazılamaz, güvenlik için kaçırılır.</p>
            </div>
        </x-slot:help>

        <x-slot:toolbar>
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-t-lg border border-b-0 border-zinc-300 bg-zinc-50 px-1.5 py-1 dark:border-zinc-700 dark:bg-zinc-900/60">
                <div class="flex flex-wrap items-center gap-0.5" x-show="tab === 'write'">
                    @unless ($inline)
                    <x-admin.button size="sm" variant="ghost" square icon="heading-2" x-on:click="line('## ')" aria-label="Başlık" title="Başlık" />
                    @endunless
                    <x-admin.button size="sm" variant="ghost" square icon="bold" x-on:click="wrap('**', '**', 'kalın')" aria-label="Kalın" title="Kalın" />
                    <x-admin.button size="sm" variant="ghost" square icon="italic" x-on:click="wrap('*', '*', 'eğik')" aria-label="Eğik" title="Eğik" />
                    <x-admin.button size="sm" variant="ghost" square icon="highlighter" x-on:click="wrap('==', '==', 'vurgu')" aria-label="Fosforlu kalem" title="Fosforlu kalem" />
                    <x-admin.button size="sm" variant="ghost" square icon="link" x-on:click="wrap('[', '](https://)', 'link metni')" aria-label="Link" title="Link" />
                    @if ($inline)
                    <x-admin.button size="sm" variant="ghost" square icon="code" x-on:click="wrap('`', '`', 'kod')" aria-label="Satır içi kod" title="Satır içi kod" />
                    @else
                    <x-admin.button size="sm" variant="ghost" square icon="list" x-on:click="line('- ')" aria-label="Liste" title="Liste" />
                    <x-admin.button size="sm" variant="ghost" square icon="code" x-on:click="block('```php', '```', 'kod')" aria-label="Kod bloğu" title="Kod bloğu" />
                    <x-admin.button size="sm" variant="ghost" square icon="sticky-note" x-on:click="sidenote()" aria-label="Kenar notu" title="Kenar notu" />
                    @endif
                    @if ($reviews)
                        <x-admin.button size="sm" variant="ghost" square icon="eye-off" x-on:click="block(':::spoiler', ':::', 'spoiler metni')" aria-label="Spoiler" title="Spoiler" />
                        <x-admin.button size="sm" variant="ghost" square icon="quote" x-on:click="block(':::replik Kişi', ':::', 'replik')" aria-label="Replik" title="Replik" />
                    @endif
                    @if ($upload)
                        <input type="file" x-ref="imageInput" wire:model="{{ $upload }}" x-on:livewire-upload-finish="$el.value = ''" accept="image/jpeg,image/png,image/webp,image/avif,image/gif" class="hidden" tabindex="-1" aria-hidden="true" />
                        <x-admin.button size="sm" variant="ghost" square icon="image" x-on:click="$refs.imageInput.click()" aria-label="Görsel ekle" title="Görsel ekle" />
                        <span wire:loading wire:target="{{ $upload }}" class="px-1 text-xs font-semibold text-accent">yükleniyor…</span>
                    @endif
                    {{ $toolbar ?? '' }}
                </div>
                <div class="ml-auto flex items-center gap-1 rounded-md bg-zinc-200/70 p-0.5 text-xs font-semibold dark:bg-zinc-800" role="tablist">
                    <button type="button" role="tab" x-on:click="tab = 'write'" x-bind:aria-selected="tab === 'write'" x-bind:class="tab === 'write' ? 'bg-white shadow-xs dark:bg-zinc-700' : 'text-zinc-500'" class="cursor-pointer rounded px-2.5 py-1">Yaz</button>
                    <button type="button" role="tab" x-on:click="preview()" x-bind:aria-selected="tab === 'preview'" x-bind:class="tab === 'preview' ? 'bg-white shadow-xs dark:bg-zinc-700' : 'text-zinc-500'" class="cursor-pointer rounded px-2.5 py-1">Önizleme</button>
                </div>
            </div>
        </x-slot:toolbar>
    </x-admin.textarea>

    <div x-cloak x-show="tab === 'preview'" class="relative overflow-hidden rounded-b-lg border border-zinc-300 dark:border-zinc-700">
        <p x-show="loading" class="absolute inset-x-0 top-0 bg-accent-soft px-3 py-1 text-xs font-semibold text-accent">Önizleme hazırlanıyor…</p>
        <iframe x-bind:srcdoc="previewHtml" title="Önizleme" class="block h-[32rem] w-full bg-white" sandbox="allow-same-origin"></iframe>
    </div>
</div>
