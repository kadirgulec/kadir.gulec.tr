<?php

use App\Enums\Section;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Projeler')] class extends Component {
    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()->with('technologies')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function sort(int $id, int $position): void
    {
        Project::query()->findOrFail($id)->moveTo($position);

        unset($this->projects);
        $this->dispatch('toast', text: 'Sıralama kaydedildi.');
    }
}; ?>

<div>
    <x-admin.page-header heading="Projeler" description="Sitedeki sırayı tutup sürükleyerek değiştir." :dot="Section::Projects->adminDotClass()">
        <x-slot:actions>
            <x-admin.button variant="primary" icon="plus" :href="route('admin.projects.create')" wire:navigate>Yeni proje</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($this->projects->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="folder-git-2" heading="Henüz proje yok">
                İlk projeyi ekleyince sitede listelenir.
                <x-slot:actions>
                    <x-admin.button variant="primary" icon="plus" size="sm" :href="route('admin.projects.create')" wire:navigate>Yeni proje</x-admin.button>
                </x-slot:actions>
            </x-admin.empty>
        </x-admin.card>
    @else
        <ul wire:sort="sort" class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
            @foreach ($this->projects as $project)
                <li wire:key="project-{{ $project->id }}" wire:sort:item="{{ $project->id }}" class="flex items-center gap-3 px-3 py-3 sm:gap-4 sm:px-4">
                    <button type="button" wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-700 active:cursor-grabbing dark:hover:text-zinc-200" aria-label="{{ $project->name }} sırasını değiştir">
                        <x-admin.icon name="grip-vertical" />
                    </button>

                    <div class="hidden h-12 w-20 shrink-0 overflow-hidden rounded-md bg-zinc-100 sm:block dark:bg-zinc-800">
                        @if ($project->cover_path)
                            <img src="{{ $project->coverUrl(480) }}" alt="" class="size-full object-cover object-top" />
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="flex items-center gap-2 font-bold hover:text-accent">
                            <span class="truncate">{{ $project->name }}</span>
                            @if ($project->is_featured)
                                <x-admin.icon name="star" class="size-3.5 fill-current text-section-projects" label="Öne çıkan" />
                            @endif
                        </a>
                        <p class="truncate text-sm text-zinc-500">{{ $project->tagline }}</p>
                    </div>

                    <div class="hidden flex-wrap justify-end gap-1.5 md:flex">
                        <x-admin.badge :color="$project->status->color()">{{ $project->status->label() }}</x-admin.badge>
                        <x-admin.badge :color="$project->publicationState()->color()">{{ $project->publicationState()->label() }}</x-admin.badge>
                    </div>

                    <x-admin.dropdown>
                        <x-slot:trigger>
                            <x-admin.button size="sm" variant="ghost" square icon="ellipsis" aria-label="{{ $project->name }}: işlemler" />
                        </x-slot:trigger>
                        <x-admin.dropdown.item icon="pencil" :href="route('admin.projects.edit', $project)">Düzenle</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="external-link" :href="route('projects.show', $project->slug)" target="_blank">Sitede gör</x-admin.dropdown.item>
                    </x-admin.dropdown>
                </li>
            @endforeach
        </ul>
    @endif
</div>
