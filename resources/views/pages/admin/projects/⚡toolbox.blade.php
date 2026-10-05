<?php

use App\Enums\Section;
use App\Enums\ToolboxGroup;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Alet çantası · Projeler')] class extends Component {
    public string $name = '';

    public string $group = 'daily';

    /**
     * @return Collection<int, Technology>
     */
    #[Computed]
    public function technologies(): Collection
    {
        return Technology::query()->withCount('projects')->orderBy('toolbox_order')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Technology>
     */
    public function inGroup(?ToolboxGroup $group): Collection
    {
        return $this->technologies->filter(fn (Technology $technology): bool => $technology->toolbox_group === $group)->values();
    }

    /**
     * A new sticker: an existing technology goes into the group, a new name becomes a technology.
     */
    public function add(): void
    {
        $this->name = trim($this->name);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'group' => ['required', Rule::enum(ToolboxGroup::class)],
        ], attributes: ['name' => 'ad', 'group' => 'grup']);

        $technology = Technology::query()->firstOrNew(['name' => $this->name]);
        $technology->placeInToolbox(ToolboxGroup::from($this->group));

        $this->reset('name');
        unset($this->technologies);
        $this->dispatch('toast', text: "{$technology->name} alet çantasında.");
    }

    public function place(int $id, ?string $group): void
    {
        $technology = Technology::query()->findOrFail($id);
        $technology->placeInToolbox($group === null ? null : ToolboxGroup::from($group));

        unset($this->technologies);
        $this->dispatch('toast', text: $group === null ? "{$technology->name} çantadan çıktı." : "{$technology->name} taşındı.");
    }

    public function sort(int $id, int $position): void
    {
        Technology::query()->findOrFail($id)->moveInToolbox($position);

        unset($this->technologies);
        $this->dispatch('toast', text: 'Sıralama kaydedildi.');
    }

    /**
     * Only technologies no project uses: the others would vanish from their project cards.
     */
    public function delete(int $id): void
    {
        $technology = Technology::query()->withCount('projects')->findOrFail($id);

        if ($technology->projects_count > 0) {
            $this->dispatch('toast', text: "{$technology->name} projelerde kullanılıyor, silinemez.");

            return;
        }

        $technology->delete();

        unset($this->technologies);
        $this->dispatch('toast', text: "{$technology->name} silindi.");
    }
}; ?>

<div>
    <x-admin.page-header heading="Alet çantası" description="Hakkımda sayfasındaki stickerlar. Grubu seç, gruptaki sırayı sürükleyerek değiştir." :dot="Section::Projects->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('about')" icon="external-link" variant="ghost" target="_blank">Sitede gör</x-admin.button>
            <x-admin.button :href="route('admin.projects.index')" icon="arrow-left" variant="ghost" wire:navigate>Projeler</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="space-y-6">
        <x-admin.card>
            <form wire:submit="add" class="flex flex-wrap items-end gap-3">
                <x-admin.input wire:model="name" label="Ekle" placeholder="ör. Docker ya da Fransızca" class="min-w-48 flex-1" />
                <x-admin.select wire:model="group" label="Grup" :options="collect(ToolboxGroup::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()" />
                <x-admin.button type="submit" variant="primary" icon="plus" class="mb-px">Ekle</x-admin.button>
            </form>
            <p class="mt-2 text-sm text-zinc-500">Projelerde kullanılan bir adı yazarsan o teknoloji çantaya girer; yeni bir ad yeni bir teknoloji olur.</p>
        </x-admin.card>

        @foreach ([...ToolboxGroup::cases(), null] as $toolboxGroup)
            @php($items = $this->inGroup($toolboxGroup))

            <x-admin.card padding="p-0" wire:key="group-{{ $toolboxGroup?->value ?? 'none' }}">
                <x-slot:heading>
                    <span>{{ $toolboxGroup?->label() ?? 'Çantada değil' }}</span>
                    <span class="ml-1 text-sm font-normal text-zinc-500">{{ $items->count() }}</span>
                </x-slot:heading>

                @if ($toolboxGroup === null)
                    <p class="border-b border-zinc-100 px-4 py-2.5 text-sm text-zinc-500 dark:border-zinc-800">Projelerde kullanılan ama Hakkımda'da görünmeyen teknolojiler.</p>
                @endif

                @if ($items->isEmpty())
                    <p class="px-4 py-4 text-sm text-zinc-500">{{ $toolboxGroup === null ? 'Hepsi çantada.' : 'Bu grupta henüz bir şey yok.' }}</p>
                @else
                    <ul
                        @if ($toolboxGroup !== null) wire:sort="sort" @endif
                        class="divide-y divide-zinc-100 dark:divide-zinc-800"
                        aria-label="{{ $toolboxGroup?->label() ?? 'Çantada değil' }}"
                    >
                        @foreach ($items as $technology)
                            <li wire:key="technology-{{ $technology->id }}" @if ($toolboxGroup !== null) wire:sort:item="{{ $technology->id }}" @endif class="flex items-center gap-3 px-3 py-2.5 sm:px-4">
                                @if ($toolboxGroup !== null)
                                    <button type="button" wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-700 active:cursor-grabbing dark:hover:text-zinc-200" aria-label="{{ $technology->name }} sırasını değiştir">
                                        <x-admin.icon name="grip-vertical" />
                                    </button>
                                @endif

                                <span class="min-w-0 flex-1 truncate font-semibold">{{ $technology->name }}</span>
                                <span class="text-sm text-zinc-500">{{ $technology->projects_count }} proje</span>

                                <x-admin.dropdown>
                                    <x-slot:trigger>
                                        <x-admin.button size="sm" variant="ghost" square icon="ellipsis" aria-label="{{ $technology->name }}: işlemler" />
                                    </x-slot:trigger>
                                    @foreach (ToolboxGroup::cases() as $target)
                                        @if ($target !== $toolboxGroup)
                                            <x-admin.dropdown.item icon="arrow-right" wire:click="place({{ $technology->id }}, '{{ $target->value }}')">{{ $target->label() }} grubuna taşı</x-admin.dropdown.item>
                                        @endif
                                    @endforeach
                                    @if ($toolboxGroup !== null)
                                        <x-admin.dropdown.item icon="minus" wire:click="place({{ $technology->id }}, null)">Çantadan çıkar</x-admin.dropdown.item>
                                    @endif
                                    @if ($technology->projects_count === 0)
                                        <x-admin.dropdown.separator />
                                        <x-admin.dropdown.item icon="trash-2" variant="danger" wire:click="delete({{ $technology->id }})" wire:confirm="{{ $technology->name }} silinsin mi?">Sil</x-admin.dropdown.item>
                                    @endif
                                </x-admin.dropdown>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>
        @endforeach
    </div>
</div>
