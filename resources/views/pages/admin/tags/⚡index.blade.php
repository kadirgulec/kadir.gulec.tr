<?php

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Etiketler')] class extends Component {
    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    #[Locked]
    public ?int $mergingId = null;

    public string $mergeTarget = '';

    /**
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return Tag::query()->withCount(['posts', 'notes'])->orderBy('name')->get();
    }

    public function edit(int $id): void
    {
        $tag = Tag::query()->findOrFail($id);

        $this->editingId = $tag->id;
        $this->name = $tag->name;
        $this->resetValidation();
    }

    public function rename(): void
    {
        $tag = Tag::query()->findOrFail($this->editingId);

        $this->validate([
            'name' => ['required', 'string', 'max:40', Rule::unique('tags', 'name')->ignore($tag->id)],
        ], attributes: ['name' => 'etiket adı']);

        if (Tag::query()->whereKeyNot($tag->id)->where('slug', Str::slug($this->name))->exists()) {
            $this->addError('name', 'Bu adres başka bir etikette kullanılıyor. Birleştirmeyi dene.');

            return;
        }

        $tag->update(['name' => $this->name]);

        $this->reset('editingId', 'name');
        unset($this->tags);
        $this->dispatch('toast', text: 'Etiketin adı değişti.');
    }

    public function startMerge(int $id): void
    {
        $this->mergingId = Tag::query()->findOrFail($id)->id;
        $this->mergeTarget = '';
        $this->dispatch('modal-show', name: 'merge-tag');
    }

    public function merge(): void
    {
        $this->validate(['mergeTarget' => ['required', 'integer', Rule::exists('tags', 'id'), Rule::notIn([$this->mergingId])]], attributes: ['mergeTarget' => 'hedef etiket']);

        $source = Tag::query()->findOrFail($this->mergingId);
        $source->mergeInto(Tag::query()->findOrFail((int) $this->mergeTarget));

        $this->reset('mergingId', 'mergeTarget');
        unset($this->tags);
        $this->dispatch('modal-close', name: 'merge-tag');
        $this->dispatch('toast', text: 'Etiketler birleşti.');
    }

    public function delete(int $id): void
    {
        $tag = Tag::query()->findOrFail($id);

        if (! $tag->isDeletable()) {
            $this->dispatch('toast', text: 'Bu etiketin notları var. Notlar etiketsiz kalamaz, önce başka bir etiketle birleştir.', variant: 'danger');

            return;
        }

        $tag->delete();

        unset($this->tags);
        $this->dispatch('toast', text: 'Etiket silindi. Yazılar yerinde duruyor.');
    }
}; ?>

<div>
    <x-admin.page-header heading="Etiketler" description="Yazılar ve öğrendiklerim aynı etiketleri paylaşır. Yeni etiketler editörde yazarken oluşur; burada adlarını düzelt, birleştir ya da sil." />

    @if ($this->tags->isEmpty())
        <x-admin.card padding="p-0"><x-admin.empty icon="tag" heading="Henüz etiket yok" /></x-admin.card>
    @else
        <x-admin.table>
            <x-admin.table.columns>
                <x-admin.table.column>Etiket</x-admin.table.column>
                <x-admin.table.column>Yazı</x-admin.table.column>
                <x-admin.table.column>Not</x-admin.table.column>
                <x-admin.table.column align="end"><span class="sr-only">İşlemler</span></x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->tags as $tag)
                    <x-admin.table.row wire:key="tag-{{ $tag->id }}">
                        <x-admin.table.cell variant="strong">
                            @if ($editingId === $tag->id)
                                <form wire:submit="rename" class="flex items-start gap-2">
                                    <x-admin.input wire:model="name" aria-label="Etiket adı" class="flex-1" autofocus />
                                    <x-admin.button type="submit" size="sm" variant="primary" class="mt-1">Kaydet</x-admin.button>
                                    <x-admin.button size="sm" variant="ghost" class="mt-1" wire:click="$set('editingId', null)">Vazgeç</x-admin.button>
                                </form>
                            @else
                                #{{ $tag->name }} <span class="ml-1 font-mono text-xs font-normal text-zinc-500">{{ $tag->slug }}</span>
                            @endif
                        </x-admin.table.cell>
                        <x-admin.table.cell>{{ $tag->posts_count }}</x-admin.table.cell>
                        <x-admin.table.cell>{{ $tag->notes_count }}</x-admin.table.cell>
                        <x-admin.table.cell align="end">
                            <x-admin.dropdown>
                                <x-slot:trigger>
                                    <x-admin.button size="sm" variant="ghost" square icon="ellipsis" aria-label="#{{ $tag->name }}: işlemler" />
                                </x-slot:trigger>
                                <x-admin.dropdown.item icon="pencil" wire:click="edit({{ $tag->id }})">Yeniden adlandır</x-admin.dropdown.item>
                                <x-admin.dropdown.item icon="layers" wire:click="startMerge({{ $tag->id }})">Başka etiketle birleştir</x-admin.dropdown.item>
                                @if ($tag->notes_count === 0)
                                    <x-admin.dropdown.separator />
                                    <x-admin.dropdown.item icon="trash-2" variant="danger" wire:click="delete({{ $tag->id }})" wire:confirm="#{{ $tag->name }} silinsin mi? Yazılar silinmez, sadece etiketsiz kalır.">Sil</x-admin.dropdown.item>
                                @endif
                            </x-admin.dropdown>
                        </x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif

    <x-admin.modal name="merge-tag" heading="Etiketleri birleştir" description="Bu etiketin yazıları ve notları seçtiğin etikete taşınır, bu etiket silinir.">
        <form wire:submit="merge" class="space-y-4">
            <x-admin.select wire:model="mergeTarget" label="Hedef etiket" placeholder="Seç…">
                @foreach ($this->tags as $tag)
                    @if ($tag->id !== $mergingId)
                        <option value="{{ $tag->id }}">#{{ $tag->name }}</option>
                    @endif
                @endforeach
            </x-admin.select>
            <div class="flex justify-end gap-2">
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button type="submit" variant="primary">Birleştir</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
