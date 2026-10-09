<?php

use App\Jobs\CreateBackup;
use App\Support\Backups\BackupManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin'), Title('Yedekler')] class extends Component {
    use WithFileUploads;

    /** The phrase to type before a restore. */
    public const CONFIRMATION = 'GERİ YÜKLE';

    /** @var mixed */
    public $archive = null;

    /** @var array{createdAt: string, app: ?string, counts: array<string, int>, compatible: bool, problems: list<string>}|null */
    public ?array $inspection = null;

    public string $password = '';

    public string $confirmation = '';

    public bool $waiting = false;

    /**
     * @return Collection<int, array{name: string, size: int, createdAt: \Illuminate\Support\Carbon}>
     */
    #[Computed]
    public function backups(): Collection
    {
        return app(BackupManager::class)->all();
    }

    public function create(): void
    {
        $count = $this->backups->count();
        CreateBackup::dispatch();

        unset($this->backups);
        $this->waiting = $this->backups->count() === $count;
        $this->dispatch('toast', text: $this->waiting ? 'Yedek hazırlanıyor, birazdan listede.' : 'Yedek hazır.');
    }

    public function refreshList(): void
    {
        unset($this->backups);
    }

    public function downloadUrl(string $name): string
    {
        return URL::temporarySignedRoute('admin.backups.download', now()->addMinutes(10), ['name' => $name]);
    }

    public function delete(string $name, BackupManager $backups): void
    {
        $backups->delete($name);
        unset($this->backups);
        $this->dispatch('toast', text: 'Yedek silindi.');
    }

    public function updatedArchive(BackupManager $backups): void
    {
        $this->validate(['archive' => ['required', 'file', 'mimes:zip', 'max:102400']], attributes: ['archive' => 'yedek dosyası']);

        $this->inspection = $backups->inspect($this->archive->getRealPath());
        $this->reset('password', 'confirmation');
    }

    public function cancelRestore(): void
    {
        $this->reset('archive', 'inspection', 'password', 'confirmation');
    }

    public function restore(BackupManager $backups): void
    {
        abort_if($this->archive === null || ! ($this->inspection['compatible'] ?? false), 422);

        $this->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:'.self::CONFIRMATION],
        ], [
            'confirmation.in' => 'Onaylamak için tam olarak "'.self::CONFIRMATION.'" yaz.',
        ], ['password' => 'şifre', 'confirmation' => 'onay']);

        $safety = $backups->restore($this->archive->getRealPath());

        session()->flash('toast', ['text' => 'Geri yüklendi. Önceki hâlin yedeği: '.$safety, 'variant' => 'success']);
        $this->redirectRoute('admin.backups.index');
    }
}; ?>

<div class="space-y-6" @if ($waiting) wire:poll.5s="refreshList" @endif>
    <x-admin.page-header heading="Yedekler" description="Veritabanı ve bütün yüklenen dosyalar tek bir zip'te. İndirip güvenli bir yerde sakla.">
        <x-slot:actions>
            <x-admin.button variant="primary" icon="archive" wire:click="create">Yedek oluştur</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($this->backups->isEmpty())
        <x-admin.card padding="p-0"><x-admin.empty icon="archive" heading="Henüz yedek yok">"Yedek oluştur" ile ilkini al. Kuyruk worker'ı çalışıyor olmalı.</x-admin.empty></x-admin.card>
    @else
        <x-admin.table>
            <x-admin.table.columns>
                <x-admin.table.column>Yedek</x-admin.table.column>
                <x-admin.table.column>Boyut</x-admin.table.column>
                <x-admin.table.column align="end"><span class="sr-only">İşlemler</span></x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->backups as $backup)
                    <x-admin.table.row wire:key="backup-{{ $backup['name'] }}">
                        <x-admin.table.cell variant="strong">
                            <span class="font-mono text-sm">{{ $backup['name'] }}</span>
                            <span class="block text-xs font-normal text-zinc-500">{{ $backup['createdAt']->timezone(config('app.timezone'))->locale('tr')->diffForHumans() }}</span>
                        </x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ \Illuminate\Support\Number::fileSize($backup['size'], 1) }}</x-admin.table.cell>
                        <x-admin.table.cell align="end">
                            <div class="flex justify-end gap-1">
                                <x-admin.button size="sm" icon="download" :href="$this->downloadUrl($backup['name'])">İndir</x-admin.button>
                                <x-admin.button size="sm" variant="ghost" square icon="trash-2" wire:click="delete('{{ $backup['name'] }}')" wire:confirm="Bu yedek silinsin mi?" aria-label="Sil" />
                            </div>
                        </x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif

    <x-admin.card>
        <x-slot:heading>Geri yükle</x-slot:heading>

        @if ($inspection === null)
            <x-admin.text class="mb-4">Sitedeki her şeyin yerine yedektekini koyar: yazılar, filmler, hedefler, üyeler, oturumlar ve görseller. Önce mevcut hâlin otomatik bir yedeği alınır.</x-admin.text>
            <x-admin.file-upload name="archive" accept=".zip,application/zip" description="Daha önce indirdiğin bir yedek zip'i." />
        @elseif (! $inspection['compatible'])
            <div class="space-y-3">
                @foreach ($inspection['problems'] as $problem)
                    <x-admin.error :message="$problem" />
                @endforeach
                <x-admin.button wire:click="cancelRestore">Başka bir dosya seç</x-admin.button>
            </div>
        @else
            <form wire:submit="restore" class="space-y-5">
                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
                    <p class="font-bold">Bu yedek {{ $inspection['createdAt'] ? \Carbon\CarbonImmutable::parse($inspection['createdAt'])->timezone(config('app.timezone'))->locale('tr')->translatedFormat('j F Y H:i') : '?' }} tarihli{{ $inspection['app'] ? ' (kod: '.$inspection['app'].')' : '' }}:</p>
                    <p class="mt-1">
                        {{ $inspection['counts']['posts'] ?? 0 }} yazı · {{ $inspection['counts']['notes'] ?? 0 }} not · {{ $inspection['counts']['watchables'] ?? 0 }} film/dizi ·
                        {{ $inspection['counts']['goals'] ?? 0 }} hedef · {{ $inspection['counts']['monthly_reviews'] ?? 0 }} değerlendirme · {{ $inspection['counts']['projects'] ?? 0 }} proje ·
                        {{ $inspection['counts']['users'] ?? 0 }} üye · {{ $inspection['counts']['comments'] ?? 0 }} yorum
                    </p>
                    <p class="mt-2">Şu anki her şey bunlarla değişecek. Oturumlar da yedekteki hâline döner, sonra tekrar giriş yapman gerekebilir.</p>
                </div>

                <x-admin.input wire:model="password" type="password" label="Şifren" autocomplete="current-password" />
                <x-admin.input wire:model="confirmation" :label="'Onay: '.self::CONFIRMATION.' yaz'" autocomplete="off" />

                <div class="flex gap-2">
                    <x-admin.button type="submit" variant="danger" icon="rotate-ccw">Geri yükle</x-admin.button>
                    <x-admin.button variant="ghost" wire:click="cancelRestore">Vazgeç</x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.card>
</div>
