<?php

use App\Actions\Roles\DeleteRole;
use App\Actions\Roles\SaveRole;
use App\Enums\Permission;
use App\Models\Role;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component {
    public Role $role;

    public string $label = '';

    /** @var list<string> */
    public array $permissions = [];

    public function mount(Role $role): void
    {
        $this->role = $role;
        $this->label = $role->displayName();
        $this->permissions = $role->permissions->pluck('name')->values()->all();
    }

    public function save(SaveRole $saveRole): void
    {
        $this->role = $saveRole->handle($this->role, ['label' => $this->label, 'permissions' => $this->permissions]);

        $this->dispatch('toast', text: 'Rol kaydedildi.');
    }

    public function delete(DeleteRole $deleteRole): void
    {
        $deleteRole->handle($this->role);

        session()->flash('toast', ['text' => 'Rol silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function render(): mixed
    {
        return $this->view()->title($this->role->displayName().' · Roller');
    }
}; ?>

<div class="space-y-6">
    <x-admin.page-header :heading="$role->displayName()" :description="$role->isSystem() ? 'Sistem rolü: adı değişebilir, silinemez.' : null">
        <x-slot:actions>
            <x-admin.button :href="route('admin.roles.index')" icon="arrow-left" variant="ghost" wire:navigate>Roller</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" class="space-y-6">
        <x-admin.card>
            <x-admin.input wire:model="label" label="Ad" :description="'Kodda kullanılan sabit ad: '.$role->name" />
        </x-admin.card>

        <x-admin.card>
            <x-slot:heading>İzinler</x-slot:heading>

            @if ($role->isAdmin())
                <x-admin.text>Admin her şeye yetkilidir, izinleri ayrıca seçilmez.</x-admin.text>
            @else
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach (Permission::grouped() as $group => $groupPermissions)
                        <fieldset class="space-y-3" wire:key="group-{{ $group }}">
                            <legend class="mb-2 text-xs font-bold tracking-wide text-zinc-500 uppercase">{{ $group }}</legend>
                            @foreach ($groupPermissions as $permission)
                                <x-admin.checkbox
                                    wire:model="permissions"
                                    :value="$permission->value"
                                    :label="$permission->label()"
                                    :description="$permission->value"
                                    wire:key="permission-{{ $permission->value }}"
                                />
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>
                <x-admin.error :message="$errors->first('permissions.*')" class="mt-3" />
            @endif
        </x-admin.card>

        <div class="flex flex-wrap items-center justify-between gap-3">
            @unless ($role->isSystem())
                <x-admin.modal.trigger name="delete-role">
                    <x-admin.button variant="ghost" icon="trash-2" class="text-red-600 dark:text-red-400">Rolü sil</x-admin.button>
                </x-admin.modal.trigger>
            @else
                <span></span>
            @endunless

            <x-admin.button type="submit" variant="primary">Kaydet</x-admin.button>
        </div>
    </form>

    <x-admin.modal name="delete-role" :heading="$role->displayName().' silinsin mi?'" description="Bu role sahip kullanıcılar rolü kaybeder.">
        <x-slot:footer>
            <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
            <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>
