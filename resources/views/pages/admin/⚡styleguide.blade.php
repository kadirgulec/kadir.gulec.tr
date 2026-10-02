<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
 * Every admin component on one page (only outside production).
 */
new #[Layout('layouts::admin'), Title('Stil rehberi')] class extends Component {
    public string $title = '';

    public string $body = '';

    public string $status = 'live';

    public bool $isFeatured = true;

    public bool $agreed = false;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'min:3'],
            'body' => ['required'],
            'agreed' => ['accepted'],
        ]);

        $this->dispatch('toast', text: 'Kaydedildi.');
    }

    public function sort(string $column): void
    {
        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    public function delete(): void
    {
        $this->dispatch('modal-close', name: 'confirm-delete');
        $this->dispatch('toast', text: 'Silindi (aslında değil).', variant: 'danger');
    }

    /**
     * @return list<array{name: string, status: string, year: int}>
     */
    public function rows(): array
    {
        $rows = [
            ['name' => 'CoMon', 'status' => 'Yayında', 'year' => 2024],
            ['name' => 'Çalışan Portalı', 'status' => 'Arşiv', 'year' => 2023],
            ['name' => 'kadir.gulec.tr', 'status' => 'Yapım aşamasında', 'year' => 2026],
        ];

        usort($rows, fn (array $a, array $b): int => $a[$this->sortBy] <=> $b[$this->sortBy]);

        return $this->sortDirection === 'asc' ? $rows : array_reverse($rows);
    }
}; ?>

<div class="space-y-10">
    <x-admin.page-header heading="Stil rehberi" description="Admin bileşenlerinin hepsi bir arada." :dot="\App\Enums\Section::Home->adminDotClass()">
        <x-slot:actions>
            <x-admin.button icon="external-link" :href="route('home')">Siteyi aç</x-admin.button>
            <x-admin.button variant="primary" icon="plus">Yeni kayıt</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <section class="space-y-3">
        <x-admin.heading>Butonlar</x-admin.heading>
        <div class="flex flex-wrap items-center gap-2">
            <x-admin.button variant="primary">Primary</x-admin.button>
            <x-admin.button>Outline</x-admin.button>
            <x-admin.button variant="subtle">Subtle</x-admin.button>
            <x-admin.button variant="ghost">Ghost</x-admin.button>
            <x-admin.button variant="danger" icon="trash-2">Sil</x-admin.button>
            <x-admin.button size="sm" icon="pencil">Küçük</x-admin.button>
            <x-admin.button square icon="ellipsis" aria-label="Diğer" />
            <x-admin.button disabled>Devre dışı</x-admin.button>
            <x-admin.button wire:click="$refresh" icon="refresh-cw">Yükleniyor göstergesi</x-admin.button>
        </div>
    </section>

    <section class="space-y-3">
        <x-admin.heading>Rozetler</x-admin.heading>
        <div class="flex flex-wrap gap-2">
            <x-admin.badge>Taslak</x-admin.badge>
            <x-admin.badge color="green" icon="check">Yayında</x-admin.badge>
            <x-admin.badge color="yellow">Zamanlanmış</x-admin.badge>
            <x-admin.badge color="red">Engelli</x-admin.badge>
            <x-admin.badge color="blue">Bilgi</x-admin.badge>
            <x-admin.badge color="accent">Admin</x-admin.badge>
            <x-admin.badge color="posts">Yazılar</x-admin.badge>
            <x-admin.badge color="watched">İzlediklerim</x-admin.badge>
            <x-admin.badge color="goals">Hedefler</x-admin.badge>
            <x-admin.badge color="projects">Projeler</x-admin.badge>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <x-admin.card>
            <x-slot:heading>Form</x-slot:heading>

            <form wire:submit="save" class="space-y-5">
                <x-admin.input wire:model="title" label="Başlık" description="Ziyaretçinin gördüğü ad." help="Slug bu başlıktan otomatik üretilir." />
                <x-admin.input wire:model="title" label="Arama" icon="search" placeholder="Ara…" />
                <x-admin.select wire:model="status" label="Durum" :options="['in-progress' => 'Yapım aşamasında', 'live' => 'Yayında', 'archived' => 'Arşiv']" />
                <x-admin.textarea wire:model="body" label="Metin" mono rows="5" placeholder="Markdown…">
                    <x-slot:help>
                        <p><code>==vurgu==</code> fosforlu kalem, <code>[^1]</code> kenar notu.</p>
                    </x-slot:help>
                </x-admin.textarea>
                <x-admin.switch wire:model="isFeatured" label="Öne çıkar" description="Ana sayfada gösterilir." />
                <x-admin.checkbox wire:model="agreed" label="Kontrol ettim" description="Kaydetmek için işaretle." />

                <div class="flex justify-end gap-2">
                    <x-admin.button variant="ghost" wire:click="$set('title', '')">Temizle</x-admin.button>
                    <x-admin.button type="submit" variant="primary">Kaydet</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Menü, modal, tooltip</x-slot:heading>
                <x-slot:actions>
                    <x-admin.dropdown>
                        <x-slot:trigger>
                            <x-admin.button size="sm" square variant="ghost" icon="ellipsis" aria-label="İşlemler" />
                        </x-slot:trigger>
                        <x-admin.dropdown.item icon="pencil">Düzenle</x-admin.dropdown.item>
                        <x-admin.dropdown.item icon="copy">Kopyala</x-admin.dropdown.item>
                        <x-admin.dropdown.separator />
                        <x-admin.dropdown.item icon="trash-2" variant="danger">Sil</x-admin.dropdown.item>
                    </x-admin.dropdown>
                </x-slot:actions>

                <div class="flex flex-wrap items-center gap-3">
                    <x-admin.modal.trigger name="confirm-delete">
                        <x-admin.button variant="danger" icon="trash-2">Silme onayı</x-admin.button>
                    </x-admin.modal.trigger>
                    <x-admin.button wire:click="$dispatch('toast', { text: 'Bir bilgi notu.', variant: 'info' })">Toast</x-admin.button>
                    <span class="inline-flex items-center gap-1.5 text-sm">
                        İpucu
                        <x-admin.tooltip>Hover, odak ya da dokunuşla açılır. Escape kapatır.</x-admin.tooltip>
                    </span>
                </div>
            </x-admin.card>

            <x-admin.card padding="p-0">
                <x-admin.empty icon="file-text" heading="Henüz yazı yok">
                    İlk yazını yazınca burada listelenir.
                    <x-slot:actions>
                        <x-admin.button variant="primary" icon="plus" size="sm">Yeni yazı</x-admin.button>
                    </x-slot:actions>
                </x-admin.empty>
            </x-admin.card>
        </div>
    </section>

    <section class="space-y-3">
        <x-admin.heading>Tablo</x-admin.heading>
        <x-admin.table>
            <x-admin.table.columns>
                <x-admin.table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Ad</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column sortable :sorted="$sortBy === 'year'" :direction="$sortDirection" wire:click="sort('year')">Yıl</x-admin.table.column>
                <x-admin.table.column align="end"><span class="sr-only">İşlemler</span></x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->rows() as $row)
                    <x-admin.table.row wire:key="row-{{ $row['name'] }}">
                        <x-admin.table.cell variant="strong">{{ $row['name'] }}</x-admin.table.cell>
                        <x-admin.table.cell><x-admin.badge>{{ $row['status'] }}</x-admin.badge></x-admin.table.cell>
                        <x-admin.table.cell class="font-mono">{{ $row['year'] }}</x-admin.table.cell>
                        <x-admin.table.cell align="end">
                            <x-admin.button size="sm" variant="ghost" icon="pencil">Düzenle</x-admin.button>
                        </x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    </section>

    <x-admin.modal name="confirm-delete" heading="Silinsin mi?" description="Bu işlem geri alınamaz.">
        <x-slot:footer>
            <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
            <x-admin.button variant="danger" wire:click="delete">Sil</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>
