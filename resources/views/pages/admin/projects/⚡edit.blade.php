<?php

use App\Actions\Projects\SaveProject;
use App\Enums\ProjectStatus;
use App\Enums\Section;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Technology;
use App\Support\Images\ImageStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public ProjectForm $form;

    /** @var array<int, mixed> New gallery uploads, stored right away. */
    public array $newImages = [];

    /** @var array<int, string> Captions of the gallery images by id. */
    public array $captions = [];

    public string $logDate = '';

    public string $logBody = '';

    public ?int $editingLogId = null;

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->form->setProject($project);
            $this->loadCaptions();
        } else {
            $this->form->started_year = now()->year;
        }

        $this->logDate = now()->toDateString();
    }

    public function save(SaveProject $saveProject): void
    {
        $isNew = $this->form->project === null;
        $project = $this->form->store($saveProject);

        if ($isNew) {
            session()->flash('toast', ['text' => 'Proje oluşturuldu.', 'variant' => 'success']);
            $this->redirectRoute('admin.projects.edit', $project, navigate: true);

            return;
        }

        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function publishNow(): void
    {
        $this->form->published_at = now()->format('Y-m-d\TH:i');
    }

    public function updatedNewImages(ImageStore $images): void
    {
        $this->validate(['newImages' => ['array', 'max:12'], 'newImages.*' => ['image', 'max:10240']], attributes: ['newImages.*' => 'görsel']);

        $project = $this->project();

        foreach ($this->newImages as $upload) {
            $project->images()->create([
                'path' => $images->store($upload->getRealPath(), 'projects'),
                'sort_order' => (int) $project->images()->max('sort_order') + 1,
            ]);
        }

        $this->newImages = [];
        $this->loadCaptions();
        $this->dispatch('toast', text: 'Görseller eklendi.');
    }

    public function saveCaption(int $imageId): void
    {
        $this->validate(['captions.'.$imageId => ['nullable', 'string', 'max:120']]);

        $this->project()->images()->findOrFail($imageId)->update(['caption' => $this->captions[$imageId] ?: null]);
    }

    public function sortImage(int $id, int $position): void
    {
        $this->project()->images()->findOrFail($id)->moveTo($position);
        $this->project()->unsetRelation('images');
    }

    public function deleteImage(int $imageId): void
    {
        $this->project()->images()->findOrFail($imageId)->delete();
        $this->loadCaptions();
        $this->dispatch('toast', text: 'Görsel silindi.');
    }

    public function editLog(int $id): void
    {
        $entry = $this->project()->devlog()->findOrFail($id);

        $this->editingLogId = $entry->id;
        $this->logDate = $entry->date->toDateString();
        $this->logBody = $entry->body;
    }

    public function cancelLog(): void
    {
        $this->reset('editingLogId', 'logBody');
        $this->logDate = now()->toDateString();
        $this->resetValidation(['logDate', 'logBody']);
    }

    public function saveLog(): void
    {
        $validated = $this->validate([
            'logDate' => ['required', 'date', 'before_or_equal:today'],
            'logBody' => ['required', 'string', 'max:5000'],
        ], attributes: ['logDate' => 'tarih', 'logBody' => 'not']);

        $attributes = ['date' => $validated['logDate'], 'body' => $validated['logBody']];

        if ($this->editingLogId !== null) {
            $this->project()->devlog()->findOrFail($this->editingLogId)->update($attributes);
        } else {
            $this->project()->devlog()->create($attributes);
        }

        $this->cancelLog();
        $this->dispatch('toast', text: 'Devlog kaydedildi.');
    }

    public function deleteLog(int $id): void
    {
        $this->project()->devlog()->findOrFail($id)->delete();
        $this->dispatch('toast', text: 'Devlog girdisi silindi.');
    }

    public function delete(): void
    {
        $this->project()->delete();

        session()->flash('toast', ['text' => 'Proje silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.projects.index', navigate: true);
    }

    #[Computed]
    public function coverPreview(): ?string
    {
        if ($this->form->cover !== null && method_exists($this->form->cover, 'temporaryUrl')) {
            try {
                return $this->form->cover->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->form->removeCover ? null : $this->form->project?->coverUrl(960);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function technologyOptions(): array
    {
        return Technology::query()->orderBy('name')->pluck('name')->all();
    }

    private function project(): Project
    {
        abort_if($this->form->project === null, 404);

        return $this->form->project;
    }

    private function loadCaptions(): void
    {
        $this->captions = $this->project()->images()->get()->mapWithKeys(fn (ProjectImage $image): array => [$image->id => (string) $image->caption])->all();
    }

    public function render(): mixed
    {
        return $this->view()->title(($this->form->project?->name ?? 'Yeni proje').' · Projeler');
    }
}; ?>

@php
    $project = $form->project;
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="$project?->name ?? 'Yeni proje'" :dot="Section::Projects->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.projects.index')" icon="arrow-left" variant="ghost" wire:navigate>Projeler</x-admin.button>
            @if ($project)
                <x-admin.button :href="route('projects.show', $project->slug)" icon="external-link" target="_blank">{{ $project->isPublished() ? 'Sitede gör' : 'Önizle' }}</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <x-admin.card>
                <x-slot:heading>Temel bilgiler</x-slot:heading>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input wire:model="form.name" label="Ad" class="sm:col-span-2" />
                    <x-admin.input wire:model="form.tagline" label="Tek cümle" description="Kartta ve paylaşımlarda görünür." class="sm:col-span-2" />
                    <x-admin.select wire:model="form.status" label="Durum damgası" :options="collect(ProjectStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
                    <x-admin.input wire:model="form.started_year" type="number" label="Başlangıç yılı" min="1990" />
                    <x-admin.input wire:model="form.demo_url" type="url" label="Demo adresi" placeholder="https://" mono />
                    <x-admin.input wire:model="form.repo_url" type="url" label="GitHub adresi" placeholder="https://github.com/…" mono />
                    <x-admin.combobox wire:model="form.technologyNames" :options="$this->technologyOptions" label="Teknolojiler" description="Sırası kartlardaki sıradır. Yeni bir ad yazarsan oluşturulur." class="sm:col-span-2" />
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Vaka çalışması</x-slot:heading>
                <x-admin.markdown wire:model="form.body" section="projects" description="Hangi problemi çözüyor, neden yapıldı, teknik kararlar, öğrenilenler, şu anki durum. ## ile başlık aç." />
            </x-admin.card>

            @if ($project)
                <x-admin.card>
                    <x-slot:heading>Galeri</x-slot:heading>

                    <x-admin.file-upload name="newImages" multiple description="Detay sayfasında polaroid olarak, kapaktan sonra gösterilir.">
                        @if ($project->images->isNotEmpty())
                            <ul wire:sort="sortImage" class="mb-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($project->images as $image)
                                    <li wire:key="image-{{ $image->id }}" wire:sort:item="{{ $image->id }}" class="flex gap-3 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                                        <button type="button" wire:sort:handle class="cursor-grab text-zinc-400" aria-label="Sırasını değiştir"><x-admin.icon name="grip-vertical" /></button>
                                        <img src="{{ $image->url(480) }}" alt="" class="h-16 w-24 shrink-0 rounded object-cover object-top" />
                                        <div class="min-w-0 flex-1 space-y-1.5">
                                            <x-admin.input wire:model="captions.{{ $image->id }}" wire:blur="saveCaption({{ $image->id }})" placeholder="altyazı" aria-label="Altyazı" />
                                            <x-admin.button size="sm" variant="ghost" icon="trash-2" wire:click="deleteImage({{ $image->id }})" wire:confirm="Bu görsel silinsin mi?" class="text-red-600 dark:text-red-400">Sil</x-admin.button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-admin.file-upload>
                </x-admin.card>

                <x-admin.card>
                    <x-slot:heading>Devlog</x-slot:heading>

                    <div class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                            <x-admin.input wire:model="logDate" type="date" label="Tarih" />
                            <x-admin.textarea wire:model="logBody" label="{{ $editingLogId ? 'Girdiyi düzenle' : 'Yeni girdi' }}" rows="3" placeholder="Bugün ne oldu? (Markdown)" />
                        </div>
                        <div class="flex justify-end gap-2">
                            @if ($editingLogId)
                                <x-admin.button variant="ghost" wire:click="cancelLog">Vazgeç</x-admin.button>
                            @endif
                            <x-admin.button wire:click="saveLog" icon="save">{{ $editingLogId ? 'Girdiyi kaydet' : 'Girdi ekle' }}</x-admin.button>
                        </div>

                        @if ($project->devlog->isNotEmpty())
                            <ol class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                                @foreach ($project->devlog as $entry)
                                    <li wire:key="log-{{ $entry->id }}" class="flex items-start gap-3 p-3 text-sm">
                                        <span class="w-24 shrink-0 font-mono text-xs text-zinc-500">{{ $entry->date->format('d.m.Y') }}</span>
                                        <p class="flex-1 text-zinc-700 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($entry->body, 160) }}</p>
                                        <div class="flex shrink-0 gap-1">
                                            <x-admin.button size="sm" variant="ghost" square icon="pencil" wire:click="editLog({{ $entry->id }})" aria-label="Düzenle" />
                                            <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="deleteLog({{ $entry->id }})" wire:confirm="Bu devlog girdisi silinsin mi?" aria-label="Sil" />
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </x-admin.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Yayın</x-slot:heading>
                @if ($project)
                    <x-slot:actions>
                        <x-admin.badge :color="$project->publicationState()->color()">{{ $project->publicationState()->label() }}</x-admin.badge>
                    </x-slot:actions>
                @endif

                <div class="space-y-4">
                    <x-admin.input wire:model="form.published_at" type="datetime-local" label="Yayın tarihi" description="Boş: taslak. Gelecekte: o an kendiliğinden yayınlanır." />
                    <div class="flex gap-2">
                        <x-admin.button size="sm" variant="subtle" wire:click="publishNow">Şimdi</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="$set('form.published_at', '')">Taslağa al</x-admin.button>
                    </div>
                    <x-admin.separator />
                    <x-admin.switch wire:model="form.is_featured" label="Öne çıkar" description="Ana sayfada ve listenin başında. Tek proje olabilir." />
                    <x-admin.separator />
                    <x-admin.input wire:model="form.slug" label="Adres" :description="'/projeler/'.($form->slug ?: '…').' · boş bırakırsan addan üretilir'" mono />
                    <x-admin.textarea wire:model="form.meta_description" label="SEO açıklaması" rows="2" description="Boşsa tek cümle kullanılır. En fazla 160 karakter." />
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Kapak</x-slot:heading>
                <x-admin.file-upload name="form.cover" :preview="$this->coverPreview" description="Liste kartında ve detayın tepesinde, tarayıcı çerçevesinde." />
                @if ($this->coverPreview)
                    <x-admin.button size="sm" variant="ghost" icon="trash-2" class="mt-2" wire:click="$set('form.removeCover', true)">Kapağı kaldır</x-admin.button>
                @endif
            </x-admin.card>

            @if ($project)
                <x-admin.card>
                    <x-slot:heading>Sil</x-slot:heading>
                    <x-admin.text>Görselleri ve devlog'u da silinir.</x-admin.text>
                    <x-admin.modal.trigger name="delete-project">
                        <x-admin.button variant="danger" icon="trash-2" class="mt-4 w-full">Projeyi sil</x-admin.button>
                    </x-admin.modal.trigger>
                </x-admin.card>
            @endif
        </div>
    </form>

    @if ($project)
        <x-admin.modal name="delete-project" :heading="$project->name.' silinsin mi?'" description="Bu işlem geri alınamaz.">
            <x-slot:footer>
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
