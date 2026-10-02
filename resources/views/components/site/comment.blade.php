{{-- One comment of the thread (rendered inside the site.comments Livewire component). --}}
@php
    $viewer = auth()->user();
    $isKadir = $comment->user?->isAdmin() ?? false;
@endphp

<article @class(['relative rounded-sm p-4', 'bg-paper-deep' => ! $isReply, 'bg-paper-deep/60' => $isReply, 'opacity-70' => ! $comment->isApproved() && ! $comment->trashed()])>
    @if ($comment->trashed())
        <p class="font-hand text-lg text-ink-faint">bu not silindi</p>
    @else
        <header class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="font-bold">{{ $comment->authorName() }}</span>
            @if ($isKadir)
                <x-site.logo class="stamp size-7 -rotate-12 text-section-ink" />
            @endif
            <time datetime="{{ $comment->created_at?->toIso8601String() }}" class="font-mono text-xs text-ink-faint">{{ $comment->created_at?->locale('tr')->translatedFormat('j F Y, H:i') }}</time>
            @if ($comment->edited_at)
                <span class="font-mono text-xs text-ink-faint">· düzenlendi</span>
            @endif
            @unless ($comment->isApproved())
                <span class="font-hand text-lg text-section-ink">onay bekliyor · sadece sen görüyorsun</span>
            @endunless
        </header>

        @if ($editingId === $comment->id)
            <form wire:submit="saveEdit" class="mt-3 space-y-2">
                <label for="edit-{{ $comment->id }}" class="sr-only">Yorumu düzenle</label>
                <textarea id="edit-{{ $comment->id }}" wire:model="editBody" rows="3" class="block w-full rounded-md border-2 border-rule bg-paper px-3 py-2 focus:border-section-ink focus:outline-none"></textarea>
                <x-site.form.error :message="$errors->first('editBody')" />
                <div class="flex gap-4">
                    <x-site.form.button type="submit">Kaydet</x-site.form.button>
                    <x-site.form.button variant="link" wire:click="$set('editingId', null)">Vazgeç</x-site.form.button>
                </div>
            </form>
        @else
            <div class="mt-2 space-y-3 leading-7 break-words">{!! $comment->body_html !!}</div>
        @endif

        @if ($viewer)
            <footer class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                @if (! $isReply && $comment->isApproved() && $viewer->canComment())
                    <button type="button" wire:click="startReply({{ $comment->id }})" class="cursor-pointer font-hand text-lg text-section-ink hover:underline">cevap ver</button>
                @endif
                @if ($comment->isEditableBy($viewer) && $editingId !== $comment->id)
                    <button type="button" wire:click="startEdit({{ $comment->id }})" class="cursor-pointer font-hand text-lg text-ink-soft hover:underline">düzenle</button>
                @endif
                @if ($comment->user_id === $viewer->id || $viewer->can(\App\Enums\Permission::ModerateComments->value))
                    <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="Bu yorum silinsin mi?" class="cursor-pointer font-hand text-lg text-pen-red hover:underline">sil</button>
                @endif
            </footer>
        @endif
    @endif
</article>
