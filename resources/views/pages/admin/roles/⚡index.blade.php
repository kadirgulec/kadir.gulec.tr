<?php

use App\Actions\Roles\SaveRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Roller')] class extends Component {
    public string $label = '';

    public function create(SaveRole $saveRole): void
    {
        $role = $saveRole->handle(null, ['label' => $this->label, 'permissions' => []]);

        session()->flash('toast', ['text' => 'Rol oluşturuldu. Şimdi izinlerini seç.', 'variant' => 'success']);
        $this->redirectRoute('admin.roles.edit', $role, navigate: true);
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->withCount(['users', 'permissions'])->orderBy('id')->get();
    }
}; ?>

<div>
    <x-admin.page-header heading="Roller" description="Rol oluştur, izinlerini seç, kullanıcılara ata.">
        <x-slot:actions>
            <x-admin.modal.trigger name="create-role">
                <x-admin.button variant="primary" icon="plus">Yeni rol</x-admin.button>
            </x-admin.modal.trigger>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table>
        <x-admin.table.columns>
            <x-admin.table.column>Rol</x-admin.table.column>
            <x-admin.table.column>İzinler</x-admin.table.column>
            <x-admin.table.column>Kullanıcı</x-admin.table.column>
            <x-admin.table.column align="end"><span class="sr-only">İşlemler</span></x-admin.table.column>
        </x-admin.table.columns>
        <x-admin.table.rows>
            @foreach ($this->roles as $role)
                <x-admin.table.row wire:key="role-{{ $role->id }}">
                    <x-admin.table.cell variant="strong">
                        {{ $role->displayName() }}
                        @if ($role->isSystem())
                            <x-admin.badge class="ml-1.5">sistem</x-admin.badge>
                        @endif
                        <span class="block font-mono text-xs font-normal text-zinc-500">{{ $role->name }}</span>
                    </x-admin.table.cell>
                    <x-admin.table.cell>{{ $role->isAdmin() ? 'hepsi' : $role->permissions_count }}</x-admin.table.cell>
                    <x-admin.table.cell>{{ $role->users_count }}</x-admin.table.cell>
                    <x-admin.table.cell align="end">
                        <x-admin.button size="sm" variant="ghost" icon="pencil" :href="route('admin.roles.edit', $role)" wire:navigate>Düzenle</x-admin.button>
                    </x-admin.table.cell>
                </x-admin.table.row>
            @endforeach
        </x-admin.table.rows>
    </x-admin.table>

    <x-admin.modal name="create-role" heading="Yeni rol">
        <form wire:submit="create" class="space-y-4">
            <x-admin.input wire:model="label" label="Ad" placeholder="ör. Moderatör" autofocus />

            <div class="flex justify-end gap-2">
                <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
                <x-admin.button type="submit" variant="primary">Oluştur</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
