<?php

use App\Actions\Users\BlockUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateUserRoles;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component {
    public User $user;

    /** @var list<int> */
    public array $roleIds = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->roleIds = $user->roles->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
    }

    public function saveRoles(UpdateUserRoles $updateUserRoles): void
    {
        $updateUserRoles->handle(auth()->user(), $this->user, array_map('intval', $this->roleIds));

        $this->user->load('roles');
        $this->dispatch('toast', text: 'Roller kaydedildi.');
    }

    public function toggleBlock(BlockUser $blockUser): void
    {
        if ($this->user->isBlocked()) {
            $blockUser->unblock($this->user);
            $this->dispatch('toast', text: 'Engel kaldırıldı.');

            return;
        }

        $blockUser->block(auth()->user(), $this->user);
        $this->dispatch('toast', text: 'Kullanıcı engellendi.', variant: 'info');
    }

    public function delete(DeleteUser $deleteUser): void
    {
        $deleteUser->handle(auth()->user(), $this->user);

        session()->flash('toast', ['text' => 'Kullanıcı silindi.', 'variant' => 'success']);
        $this->redirectRoute('admin.users.index', navigate: true);
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('id')->get();
    }

    public function render(): mixed
    {
        return $this->view()->title($this->user->name.' · Kullanıcılar');
    }
}; ?>

<div class="space-y-6">
    <x-admin.page-header :heading="$user->name" :description="$user->email">
        <x-slot:actions>
            <x-admin.button :href="route('admin.users.index')" icon="arrow-left" variant="ghost" wire:navigate>Kullanıcılar</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <x-admin.card>
            <x-slot:heading>Roller</x-slot:heading>

            <form wire:submit="saveRoles" class="space-y-4">
                <div class="space-y-3">
                    @foreach ($this->roles as $role)
                        <x-admin.checkbox
                            wire:model="roleIds"
                            :value="$role->id"
                            :label="$role->displayName()"
                            :description="$role->isAdmin() ? 'Her şeye yetkili.' : $role->permissions->map(fn ($permission) => \App\Enums\Permission::tryFrom($permission->name)?->label())->filter()->join(', ')"
                            wire:key="role-{{ $role->id }}"
                        />
                    @endforeach
                </div>

                <x-admin.error :message="$errors->first('roles')" />

                <div class="flex justify-end">
                    <x-admin.button type="submit" variant="primary">Rolleri kaydet</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card>
                <x-slot:heading>Durum</x-slot:heading>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Kayıt</dt><dd class="font-mono">{{ $user->created_at?->format('d.m.Y H:i') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">E-posta</dt><dd>@if ($user->hasVerifiedEmail()) <x-admin.badge color="green">Doğrulandı</x-admin.badge> @else <x-admin.badge color="yellow">Doğrulanmadı</x-admin.badge> @endif</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">İki adımlı doğrulama</dt><dd>{{ $user->hasEnabledTwoFactorAuthentication() ? 'açık' : 'kapalı' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Engel</dt><dd>@if ($user->isBlocked()) <x-admin.badge color="red">{{ $user->blocked_at->format('d.m.Y') }}</x-admin.badge> @else — @endif</dd></div>
                </dl>

                <div class="mt-5 space-y-3">
                    <x-admin.button wire:click="toggleBlock" :icon="$user->isBlocked() ? 'rotate-ccw' : 'ban'" class="w-full">
                        {{ $user->isBlocked() ? 'Engeli kaldır' : 'Engelle' }}
                    </x-admin.button>
                    <x-admin.error :message="$errors->first('block')" />
                    <x-admin.text class="text-xs">Engelli kullanıcı giriş yapabilir ama yorum yazamaz ve e-posta almaz, yorumları gizlenir.</x-admin.text>
                </div>
            </x-admin.card>

            <x-admin.card>
                <x-slot:heading>Sil</x-slot:heading>
                <x-admin.text>Yorumları "silinmiş üye" adıyla kalır, takipleri silinir.</x-admin.text>
                <x-admin.modal.trigger name="delete-user">
                    <x-admin.button variant="danger" icon="trash-2" class="mt-4 w-full">Kullanıcıyı sil</x-admin.button>
                </x-admin.modal.trigger>
                <x-admin.error :message="$errors->first('delete')" class="mt-3" />
            </x-admin.card>
        </div>
    </div>

    <x-admin.card>
        <x-slot:heading>Son yorumları</x-slot:heading>
        @php($recentComments = $user->comments()->with('commentable')->latest()->limit(10)->get())
        @if ($recentComments->isEmpty())
            <x-admin.text>Yorum yok.</x-admin.text>
        @else
            <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                @foreach ($recentComments as $comment)
                    <li class="py-2" wire:key="user-comment-{{ $comment->id }}">
                        <span class="font-mono text-xs text-zinc-500">{{ $comment->created_at?->format('d.m.Y') }}</span>
                        @unless ($comment->isApproved()) <x-admin.badge color="yellow">bekliyor</x-admin.badge> @endunless
                        <p class="text-zinc-700 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($comment->body, 200) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-admin.card>

    <x-admin.modal name="delete-user" :heading="$user->name.' silinsin mi?'" description="Bu işlem geri alınamaz.">
        <x-slot:footer>
            <x-admin.modal.close><x-admin.button variant="ghost">Vazgeç</x-admin.button></x-admin.modal.close>
            <x-admin.button variant="danger" wire:click="delete" x-on:click="$el.closest('dialog').close()">Sil</x-admin.button>
        </x-slot:footer>
    </x-admin.modal>
</div>
