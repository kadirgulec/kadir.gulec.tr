<?php

use App\Actions\Posts\SavePost;
use App\Enums\Section;
use App\Livewire\Forms\PostForm;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Images\ImageStore;
use App\Support\Markdown\Markdown;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public PostForm $form;

    /** @var mixed An in-text image, stored as soon as it arrives. */
    public $bodyImage = null;

    public function mount(?Post $post = null): void
    {
        if ($post?->exists) {
            $this->form->setPost($post);
        }
    }

    public function save(SavePost $savePost): void
    {
        $isNew = $this->form->post === null;
        $post = $this->form->store($savePost);

        if ($isNew) {
            session()->flash('toast', ['text' => 'Yazı oluşturuldu.', 'variant' => 'success']);
            $this->redirectRoute('admin.posts.edit', $post, navigate: true);

            return;
        }

        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function publishNow(): void
    {
        $this->form->published_at = now()->format('Y-m-d\TH:i');
    }

    public function updatedBodyImage(ImageStore $images): void
    {
        $this->validate(['bodyImage' => ['image', 'max:10240']], attributes: ['bodyImage' => 'görsel']);

        $base = $images->store($this->bodyImage->getRealPath(), 'posts');
        $this->bodyImage = null;

        $this->dispatch('markdown-insert', text: '![açıklama yaz]('.ImageStore::url($base, 960).')');
    }

    public function delete(): void
    {
        abort_if($this->form->post === null, 404);

        $this->form->post->delete();

        session()->flash('toast', ['text' => 'Yazı silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.posts.index', navigate: true);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function tagOptions(): array
    {
        return Tag::query()->orderBy('name')->pluck('name')->all();
    }

    #[Computed]
    public function excerptPlaceholder(): string
    {
        return app(Markdown::class)->excerpt($this->form->body) ?: 'Boş bırakırsan ilk paragraftan üretilir.';
    }

    public function render(): mixed
    {
        return $this->view()->title(($this->form->post?->title ?? 'Yeni yazı').' · Yazılar');
    }
}; ?>

@php
    $post = $form->post;
@endphp

<div class="space-y-6">
    <x-admin.page-header :heading="$post?->title ?? 'Yeni yazı'" :dot="Section::Posts->adminDotClass()">
        <x-slot:actions>
            <x-admin.button :href="route('admin.posts.index')" icon="arrow-left" variant="ghost" wire:navigate>Yazılar</x-admin.button>
            @if ($post)
                <x-admin.button :href="route('posts.show', $post->slug)" icon="external-link" target="_blank">{{ $post->isPublished() ? 'Sitede gör' : 'Önizle' }}</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <div class="min-w-0 space-y-6">
            <x-admin.card>
                <div class="space-y-5">
                    <x-admin.input wire:model="form.title" label="Başlık" class="[&_input]:text-lg [&_input]:font-bold" />
                    <x-admin.markdown wire:model="form.body" section="posts" upload="bodyImage" rows="24" label="Metin" />
                    <x-admin.error :message="$errors->first('bodyImage')" />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Yayın</x-slot:heading>
                @if ($post)
                    <x-slot:actions>
                        <x-admin.badge :color="$post->publicationState()->color()">{{ $post->publicationState()->label() }}</x-admin.badge>
                    </x-slot:actions>
                @endif

                <div class="space-y-4">
                    <x-admin.input wire:model="form.published_at" type="datetime-local" label="Yayın tarihi" description="Boş: taslak. Gelecekte: o an kendiliğinden yayınlanır." />
                    <div class="flex gap-2">
                        <x-admin.button size="sm" variant="subtle" wire:click="publishNow">Şimdi</x-admin.button>
                        <x-admin.button size="sm" variant="ghost" wire:click="$set('form.published_at', '')">Taslağa al</x-admin.button>
                    </div>
                    <x-admin.separator />
                    <x-admin.combobox wire:model="form.tagNames" :options="$this->tagOptions" label="Etiketler" />
                    <x-admin.switch wire:model="form.is_featured" label="Öne çıkar" description="Fihristin üstünde, en yeni iki öne çıkan gösterilir." />
                    <x-admin.button type="submit" variant="primary" class="w-full">Kaydet</x-admin.button>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Özet ve adres</x-slot:heading>
                <div class="space-y-4">
                    <x-admin.textarea wire:model="form.excerpt" label="Özet" rows="3" :placeholder="$this->excerptPlaceholder" description="Öne çıkan girdide ve ana sayfada görünür." />
                    <x-admin.input wire:model="form.slug" label="Adres" :description="'/yazilar/'.($form->slug ?: '…').' · boşsa başlıktan üretilir'" mono />
                    <x-admin.textarea wire:model="form.meta_description" label="SEO açıklaması" rows="2" description="Boşsa özet kullanılır. En fazla 160 karakter." />
                </div>
            </x-admin.card>

            @if ($post)
                <x-admin.card>
                    <x-slot:heading>Sil</x-slot:heading>
                    <x-admin.modal.trigger name="delete-post">
                        <x-admin.button variant="danger" icon="trash-2" class="w-full">Yazıyı sil</x-admin.button>
                    </x-admin.modal.trigger>
                </x-admin.card>
            @endif
        </div>
    </form>

    @if ($post)
        <x-admin.modal name="delete-post" :heading="'„'.$post->title.'“ silinsin mi?'" description="Bu işlem geri alınamaz.">
            <x-slot:footer>
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
