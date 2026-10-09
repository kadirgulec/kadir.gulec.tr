<?php

use App\Actions\Comments\ModerateComment;
use App\Actions\Users\BlockUser;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Yorumlar')] class extends Component {
    use WithPagination;

    #[Url(as: 'durum', except: 'bekleyen')]
    public string $filter = 'bekleyen';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Comment>
     */
    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        return Comment::query()
            ->with(['user', 'commentable'])
            ->when($this->filter === 'bekleyen', fn ($query) => $query->whereNull('approved_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Comment::query()->whereNull('approved_at')->count();
    }

    public function approve(int $id, ModerateComment $moderate): void
    {
        $moderate->approve(Comment::query()->findOrFail($id));
        unset($this->comments, $this->pendingCount);
        $this->dispatch('toast', text: 'Yorum onaylandı.');
    }

    public function delete(int $id, ModerateComment $moderate): void
    {
        $moderate->delete(Comment::query()->findOrFail($id));
        unset($this->comments, $this->pendingCount);
        $this->dispatch('toast', text: 'Yorum silindi.');
    }

    public function blockAuthor(int $id, BlockUser $blockUser, ModerateComment $moderate): void
    {
        $comment = Comment::query()->with('user')->findOrFail($id);
        abort_if($comment->user === null, 404);

        $blockUser->block(auth()->user(), $comment->user);

        if (! $comment->isApproved()) {
            $moderate->delete($comment);
        }

        unset($this->comments, $this->pendingCount);
        $this->dispatch('toast', text: $comment->user->name.' engellendi.', variant: 'info');
    }
}; ?>

<div>
    <x-admin.page-header heading="Yorumlar" description="Bir üyenin ilk yorumu onayını bekler; sonrakiler doğrudan yayınlanır." />

    <div class="mb-4 flex items-center gap-2">
        <x-admin.button size="sm" :variant="$filter === 'bekleyen' ? 'subtle' : 'ghost'" wire:click="$set('filter', 'bekleyen')">Bekleyenler <x-admin.badge :color="$this->pendingCount ? 'yellow' : 'zinc'">{{ $this->pendingCount }}</x-admin.badge></x-admin.button>
        <x-admin.button size="sm" :variant="$filter === 'hepsi' ? 'subtle' : 'ghost'" wire:click="$set('filter', 'hepsi')">Hepsi</x-admin.button>
    </div>

    @if ($this->comments->isEmpty())
        <x-admin.card padding="p-0"><x-admin.empty icon="message-square" heading="{{ $filter === 'bekleyen' ? 'Bekleyen yorum yok' : 'Henüz yorum yok' }}" /></x-admin.card>
    @else
        <x-admin.table :paginate="$this->comments">
            <x-admin.table.columns>
                <x-admin.table.column>Yorum</x-admin.table.column>
                <x-admin.table.column>Nerede</x-admin.table.column>
                <x-admin.table.column align="end"><span class="sr-only">İşlemler</span></x-admin.table.column>
            </x-admin.table.columns>
            <x-admin.table.rows>
                @foreach ($this->comments as $comment)
                    <x-admin.table.row wire:key="comment-{{ $comment->id }}">
                        <x-admin.table.cell>
                            <p class="font-bold text-zinc-900 dark:text-white">
                                @if ($comment->user)
                                    <a href="{{ route('admin.users.show', $comment->user) }}" wire:navigate class="hover:text-accent">{{ $comment->authorName() }}</a>
                                @else
                                    {{ $comment->authorName() }}
                                @endif
                                <span class="ml-1 font-mono text-xs font-normal text-zinc-500">{{ $comment->created_at?->format('d.m.Y H:i') }}</span>
                                @unless ($comment->isApproved())
                                    <x-admin.badge color="yellow" class="ml-1">bekliyor</x-admin.badge>
                                @endunless
                                @if ($comment->user?->isBlocked())
                                    <x-admin.badge color="red" class="ml-1">engelli</x-admin.badge>
                                @endif
                            </p>
                            <p class="mt-1 max-w-xl whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($comment->body, 400) }}</p>
                        </x-admin.table.cell>
                        <x-admin.table.cell>
                            @if ($comment->commentable instanceof Post)
                                <x-admin.link :href="route('posts.show', $comment->commentable->slug).'#yorumlar'" external>{{ \Illuminate\Support\Str::limit($comment->commentable->title, 40) }}</x-admin.link>
                            @elseif ($comment->commentable instanceof \App\Models\MonthlyReview)
                                <x-admin.link :href="url($comment->commentable->publicPath()).'#yorumlar'" external>{{ $comment->commentable->title() }}</x-admin.link>
                            @endif
                        </x-admin.table.cell>
                        <x-admin.table.cell align="end">
                            <div class="flex justify-end gap-1">
                                @unless ($comment->isApproved())
                                    <x-admin.button size="sm" variant="primary" icon="check" wire:click="approve({{ $comment->id }})">Onayla</x-admin.button>
                                @endunless
                                <x-admin.button size="sm" variant="ghost" icon="trash-2" wire:click="delete({{ $comment->id }})" wire:confirm="Bu yorum silinsin mi?">Sil</x-admin.button>
                                @if ($comment->user && ! $comment->user->isBlocked() && ! $comment->user->isAdmin())
                                    <x-admin.button size="sm" variant="ghost" icon="ban" wire:click="blockAuthor({{ $comment->id }})" wire:confirm="{{ $comment->user->name }} engellensin mi? Yorum yazamaz, yorumları gizlenir.">Engelle</x-admin.button>
                                @endif
                            </div>
                        </x-admin.table.cell>
                    </x-admin.table.row>
                @endforeach
            </x-admin.table.rows>
        </x-admin.table>
    @endif
</div>
