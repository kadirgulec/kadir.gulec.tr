<?php

use App\Actions\Notes\SaveNote;
use App\Enums\Section;
use App\Livewire\Forms\NoteForm;
use App\Models\Note;
use App\Models\Tag;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component {
    public NoteForm $form;

    public function mount(?Note $note = null): void
    {
        if ($note?->exists) {
            $this->form->setNote($note);

            return;
        }

        $this->form->startNew();
    }

    public function save(SaveNote $saveNote): void
    {
        $isNew = $this->form->note === null;
        $note = $this->form->store($saveNote);

        if ($isNew) {
            session()->flash('toast', ['text' => 'Not panoya yapıştı.', 'variant' => 'success']);
            $this->redirectRoute('admin.notes.edit', $note, navigate: true);

            return;
        }

        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function publishNow(): void
    {
        $this->form->published_at = now()->format('Y-m-d\TH:i');
    }

    public function delete(): void
    {
        abort_if($this->form->note === null, 404);

        $this->form->note->delete();

        session()->flash('toast', ['text' => 'Not silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.notes.index', navigate: true);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function tagOptions(): array
    {
        return Tag::query()->orderBy('name')->pluck('name')->all();
    }

    public function render(): mixed
    {
        return $this->view()->title(($this->form->note ? 'Not #'.$this->form->note->id : 'Yeni not').' · Öğrendiklerim');
    }
}; ?>

@php
    $note = $form->note;
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="$note ? 'Not #'.$note->id : 'Yeni not'" :dot="Section::Notes->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.notes.index')" icon="arrow-left" variant="ghost" wire:navigate>Öğrendiklerim</x-admin.button>
            @if ($note && Route::has('notes.show'))
                <x-admin.button :href="route('notes.show', $note)" icon="external-link" target="_blank">{{ $note->isPublished() ? 'Sitede gör' : 'Önizle' }}</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0">
            <x-admin.card>
                <div class="space-y-1.5">
                    <x-admin.markdown wire:model="form.body" section="notes" rows="6" label="Ne öğrendin?" inline />
                    <x-admin.char-counter field="form.body" :soft="NoteForm::SOFT_LIMIT" :hard="NoteForm::HARD_HINT" />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Yayın</x-slot:heading>
                @if ($note)
                    <x-slot:actions>
                        <x-admin.badge :color="$note->publicationState()->color()">{{ $note->publicationState()->label() }}</x-admin.badge>
                    </x-slot:actions>
                @endif

                <div class="space-y-4">
                    <x-admin.input wire:model="form.tagName" label="Etiket" list="note-tag-options" autocomplete="off" description="Tek etiket. Yoksa oluşturulur." />
                    <datalist id="note-tag-options">
                        @foreach ($this->tagOptions as $tagName)
                            <option value="{{ $tagName }}"></option>
                        @endforeach
                    </datalist>
                    <x-admin.separator />
                    <x-admin.input wire:model="form.published_at" type="datetime-local" label="Tarih" description="Post-it'teki tarih. Boş: taslak. Gelecekte: o an kendiliğinden yayınlanır." />
                    <div class="flex gap-2">
                        <x-admin.button size="sm" variant="subtle" wire:click="publishNow">Şimdi</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="$set('form.published_at', '')">Taslağa al</x-admin.button>
                    </div>
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                </div>
            </x-admin.card>

            @if ($note)
                <x-admin.card>
                    <x-slot:heading>Sil</x-slot:heading>
                    <x-admin.modal.trigger name="delete-note">
                        <x-admin.button variant="danger" icon="trash-2" class="w-full">Notu sil</x-admin.button>
                    </x-admin.modal.trigger>
                </x-admin.card>
            @endif
        </div>
    </form>

    @if ($note)
        <x-admin.modal name="delete-note" :heading="'Not #'.$note->id.' silinsin mi?'" description="Bu işlem geri alınamaz.">
            <x-slot:footer>
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
