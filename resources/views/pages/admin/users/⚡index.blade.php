<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Kullanıcılar')] class extends Component {
    use WithPagination;

    #[Url(as: 'ara', except: '')]
    public string $search = '';

    #[Url(as: 'rol', except: '')]
    public string $role = '';

    #[Url(as: 'sirala', except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(as: 'yon', except: 'desc')]
    public string $sortDirection = 'desc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role'], true)) {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'created_at'], true)) {
            return;
        }

        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $column = in_array($this->sortBy, ['name', 'created_at'], true) ? $this->sortBy : 'created_at';

        return User::query()
            ->with('roles')
            ->withCount('comments')
            ->when($this->search !== '', function ($query): void {
                $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%'));
            })
            ->when($this->role !== '', fn ($query) => $query->role($this->role))
            ->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->paginate(25);
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('id')->get();
    }
}; ?>

<div>
    <x-admin.page-header heading="Kullanıcılar" description="Kayıtlı herkes, rolleri ve durumları." />

    <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_14rem]">
        <x-admin.input wire:model.live.debounce.300ms="search" icon="search" placeholder="Ad ya da e-posta ara…" aria-label="Ara" />
        <x-admin.select wire:model.live="role" aria-label="Rol" placeholder="Bütün roller">
            @foreach ($this->roles as $roleOption)
                <option value="{{ $roleOption->name }}">{{ $roleOption->displayName() }}</option>
            @endforeach
        </x-admin.select>
    </div>

    @if ($this->users->isEmpty())
        <x-admin.card padding="p-0">
            <x-admin.empty icon="users" heading="Kimse bulunamadı">Aramayı ya da rol filtresini değiştir.</x-admin.empty>
        </x-admin.card>
    @else
        <x-admin.table :paginate="$this->users">
            <x-admin.table.columns>
                <x-admin.table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Kullanıcı</x-admin.table.column>
                <x-admin.table.column>Roller</x-admin.table.column>
                <x-admin.table.column>Durum</x-admin.table.column>
                <x-admin.table.column>Yorum</x-admin.table.column>
                <x-admin.table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Kayıt</x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->users as $user)
                    <x-admin.table.row wire:key="user-{{ $user->id }}">
                        <x-admin.table.cell variant="strong">
                            <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="block hover:text-accent">
                                {{ $user->name }}
                                <span class="block text-xs font-normal text-zinc-500">{{ $user->email }}</span>
                            </a>
                        </x-admin.table.cell>
                        <x-admin.table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->roles as $userRole)
                                    <x-admin.badge :color="$userRole->isAdmin() ? 'accent' : 'zinc'">{{ $userRole->displayName() }}</x-admin.badge>
                                @endforeach
                            </div>
                        </x-admin.table.cell>
                        <x-admin.table.cell>
                            <div class="flex flex-wrap gap-1">
                                @if ($user->isBlocked())
                                    <x-admin.badge color="red" icon="ban">Engelli</x-admin.badge>
                                @endif
                                @unless ($user->hasVerifiedEmail())
                                    <x-admin.badge color="yellow">Doğrulanmadı</x-admin.badge>
                                @endunless
                            </div>
                        </x-admin.table.cell>
                        <x-admin.table.cell class="font-mono">{{ $user->comments_count }}</x-admin.table.cell>
                        <x-admin.table.cell class="font-mono text-xs">{{ $user->created_at?->format('d.m.Y') }}</x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
