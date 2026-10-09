<?php

use App\Actions\Comments\ModerateComment;
use App\Actions\Comments\PostComment;
use App\Enums\Permission;
use App\Models\Comment;
use App\Models\MonthlyReview;
use App\Models\Post;
use App\Models\User;
use App\Rules\Honeypot;
use App\Rules\Turnstile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * Comments under a post or a monthly review: the thread (one level of
 * replies) and the form.
 */
new class extends Component {
    /** The morph name of what is commented on ("post", "monthly_review"). */
    #[Locked]
    public string $commentableType;

    #[Locked]
    public int $commentableId;

    public string $body = '';

    #[Locked]
    public ?int $replyTo = null;

    public string $replyBody = '';

    #[Locked]
    public ?int $editingId = null;

    public string $editBody = '';

    public string $website = '';

    public string $turnstileToken = '';

    public ?string $status = null;

    public function mount(Post|MonthlyReview $commentable): void
    {
        $this->commentableType = $commentable->getMorphClass();
        $this->commentableId = $commentable->id;
    }

    /**
     * Top comments with their replies: what visitors see, plus the viewer's own
     * waiting comments, plus deleted comments that still hold replies.
     *
     * @return Collection<int, Comment>
     */
    #[Computed]
    public function comments(): Collection
    {
        $userId = auth()->id();
        // Receives a query builder or a relation (eager loading the replies).
        $visible = fn (Builder|HasMany $query) => $query->where(fn (Builder $query) => $query
            ->visible()
            ->when($userId, fn (Builder $query) => $query->orWhere('user_id', $userId)));

        return Comment::query()
            ->withTrashed()
            ->where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->whereNull('parent_id')
            ->with(['user.roles', 'replies' => fn ($query) => $visible($query)->with('user.roles')])
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $visible($query->whereNull('deleted_at')))
                ->orWhere(fn (Builder $query) => $query->whereNotNull('deleted_at')->whereHas('replies', fn (Builder $query) => $query->visible())))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function post(PostComment $postComment): void
    {
        $this->submit($postComment, $this->body, null);
        $this->reset('body');
    }

    public function startReply(int $commentId): void
    {
        $this->replyTo = $commentId;
        $this->reset('replyBody', 'editingId');
    }

    public function reply(PostComment $postComment): void
    {
        $parent = $this->comments->firstWhere('id', $this->replyTo) ?? abort(404);

        $this->submit($postComment, $this->replyBody, $parent, 'replyBody');
        $this->reset('replyBody', 'replyTo');
    }

    public function startEdit(int $commentId): void
    {
        $comment = $this->ownComment($commentId);
        abort_unless($comment->isEditableBy($this->user()), 403);

        $this->editingId = $comment->id;
        $this->editBody = $comment->body;
        $this->reset('replyTo');
    }

    public function saveEdit(): void
    {
        $comment = $this->ownComment((int) $this->editingId);

        if (! $comment->isEditableBy($this->user())) {
            $this->addError('editBody', 'Düzenleme süresi doldu ('.Comment::EDIT_WINDOW.' dakika).');

            return;
        }

        $this->validate(['editBody' => ['required', 'string', 'min:2', 'max:3000']], attributes: ['editBody' => 'yorum']);

        $comment->update(['body' => trim($this->editBody), 'edited_at' => now()]);
        $this->reset('editingId', 'editBody');
        unset($this->comments);
    }

    public function delete(int $commentId, ModerateComment $moderate): void
    {
        $comment = Comment::query()->findOrFail($commentId);

        abort_unless($comment->user_id === auth()->id() || auth()->user()?->can(Permission::ModerateComments->value), 403);

        $moderate->delete($comment);
        unset($this->comments);
    }

    private function commentable(): Post|MonthlyReview
    {
        return match ($this->commentableType) {
            (new MonthlyReview)->getMorphClass() => MonthlyReview::query()->findOrFail($this->commentableId),
            default => Post::query()->findOrFail($this->commentableId),
        };
    }

    private function submit(PostComment $postComment, string $body, ?Comment $parent, string $field = 'body'): void
    {
        $this->validate([
            $field => ['required', 'string', 'min:2', 'max:3000'],
            'website' => [new Honeypot],
            'turnstileToken' => [new Turnstile(request()->ip())],
        ], attributes: [$field => 'yorum']);

        try {
            $comment = $postComment->handle($this->user(), $this->commentable(), $body, $parent);
        } finally {
            $this->turnstileToken = '';
            $this->dispatch('turnstile-reset');
        }

        $this->status = $comment->isApproved() ? null : 'Teşekkürler! İlk yorumun Kadir onaylayınca görünecek.';
        unset($this->comments);
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function ownComment(int $id): Comment
    {
        return Comment::query()->where('user_id', $this->user()->id)->findOrFail($id);
    }
}; ?>

@php
    $viewer = auth()->user();
@endphp

<section class="mt-16 max-w-2xl" aria-labelledby="yorumlar">
    <h2 id="yorumlar" class="font-display text-2xl font-semibold">Kenara düşülen notlar</h2>
    <p class="font-hand text-lg text-ink-faint">yorumlar · {{ $this->comments->count() ? $this->comments->count().' konu' : 'henüz yok' }}</p>

    @if ($this->comments->isNotEmpty())
        <ol class="mt-6 flex flex-col gap-6">
            @foreach ($this->comments as $comment)
                <li wire:key="comment-{{ $comment->id }}" class="space-y-3">
                    @include('components.site.comment', ['comment' => $comment, 'isReply' => false])

                    @if ($comment->replies->isNotEmpty())
                        <ol class="ml-6 flex flex-col gap-3 border-l-2 border-dashed border-rule pl-5 sm:ml-10">
                            @foreach ($comment->replies as $reply)
                                <li wire:key="comment-{{ $reply->id }}">
                                    @include('components.site.comment', ['comment' => $reply, 'isReply' => true])
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($replyTo === $comment->id)
                        <form wire:submit="reply" class="ml-6 space-y-3 sm:ml-10">
                            <label for="reply-{{ $comment->id }}" class="sr-only">{{ $comment->authorName() }} adlı kişiye cevap</label>
                            <textarea id="reply-{{ $comment->id }}" wire:model="replyBody" rows="3" class="block w-full rounded-md border-2 border-rule bg-paper px-3 py-2 text-ink focus:border-section-ink focus:outline-none" placeholder="Cevabın…"></textarea>
                            <x-site.form.error :message="$errors->first('replyBody')" />
                            <x-site.form.bot-check model="turnstileToken" />
                            <div class="flex items-center gap-4">
                                <x-site.form.button type="submit">Cevapla</x-site.form.button>
                                <x-site.form.button variant="link" wire:click="$set('replyTo', null)">Vazgeç</x-site.form.button>
                            </div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    <div class="mt-10">
        @guest
            <p class="rounded-md border-2 border-dashed border-rule p-4 text-ink-soft">
                Yorum yazmak için <a href="{{ route('login') }}" class="font-semibold underline decoration-section decoration-2 underline-offset-4">giriş yap</a>
                ya da <a href="{{ route('register') }}" class="font-semibold underline decoration-section decoration-2 underline-offset-4">kayıt ol</a>.
            </p>
        @else
            @if (! $viewer->hasVerifiedEmail())
                <p class="rounded-md border-2 border-dashed border-rule p-4 text-ink-soft">Yorum yazmak için önce <a href="{{ route('verification.notice') }}" class="font-semibold underline">e-postanı doğrula</a>.</p>
            @elseif (! $viewer->canComment())
                <p class="rounded-md border-2 border-dashed border-rule p-4 text-ink-soft">Bu hesapla yorum yazılamıyor.</p>
            @else
                <form wire:submit="post" class="space-y-3">
                    <label for="comment-body" class="block font-bold">Bir not bırak</label>
                    <textarea id="comment-body" wire:model="body" rows="4" class="block w-full rounded-md border-2 border-rule bg-paper px-3 py-2 text-ink focus:border-section-ink focus:outline-none" placeholder="Ne düşündün?"></textarea>
                    <x-site.form.error :message="$errors->first('body')" />
                    <x-site.form.error :message="$errors->first('website')" />
                    <x-site.form.bot-check model="turnstileToken" />
                    <div class="flex flex-wrap items-center gap-4">
                        <x-site.form.button type="submit">Gönder</x-site.form.button>
                        <span class="text-xs text-ink-faint">düz metin · linkler çalışır · {{ \App\Models\Comment::EDIT_WINDOW }} dakika içinde düzenleyebilirsin</span>
                    </div>
                    <x-site.form.status :message="$status" />
                </form>
            @endif
        @endguest
    </div>
</section>
