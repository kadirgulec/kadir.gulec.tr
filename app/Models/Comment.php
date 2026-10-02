<?php

namespace App\Models;

use App\Support\CommentFormatter;
use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A comment of a member under a post (the table is polymorphic, so films or
 * projects could get comments later). One level of replies. A member's first
 * comment waits for approval; later ones are approved right away.
 * When the author's account is deleted, user_id becomes null ("silinmiş üye").
 *
 * @property int $id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property string $body
 * @property string $body_html
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['body', 'parent_id', 'approved_at', 'edited_at'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, SoftDeletes;

    /** Minutes in which an author may still edit a comment. */
    public const EDIT_WINDOW = 15;

    protected static function booted(): void
    {
        static::saving(function (Comment $comment): void {
            if ($comment->isDirty('body')) {
                $comment->body_html = CommentFormatter::toHtml($comment->body);
            }
        });
    }

    /**
     * Comments visitors see: approved, by authors who are not blocked.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->whereNotNull('approved_at')
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhereHas('user', fn (Builder $query) => $query->whereNull('blocked_at')));
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at')->orderBy('id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isEditableBy(User $user): bool
    {
        return $this->user_id === $user->id
            && ! $this->trashed()
            && $this->created_at !== null
            && $this->created_at->addMinutes(self::EDIT_WINDOW)->isFuture();
    }

    public function authorName(): string
    {
        return $this->user->name ?? 'silinmiş üye';
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'immutable_datetime',
            'edited_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
