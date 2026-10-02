<?php

namespace App\Actions\Comments;

use App\Enums\Permission;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Writes a comment or a reply. The first comment of a member waits for
 * Kadir's approval; once a member has an approved comment, the next ones
 * appear right away. Replies are one level deep: a reply to a reply goes
 * under the same top comment.
 */
class PostComment
{
    public const PER_MINUTE = 5;

    public const PER_DAY = 30;

    public function handle(User $author, Model $commentable, string $body, ?Comment $replyTo = null): Comment
    {
        if (! $author->canComment()) {
            throw ValidationException::withMessages(['body' => 'Yorum yazamazsın.']);
        }

        $body = trim($body);

        if (mb_strlen($body) < 2 || mb_strlen($body) > 3000) {
            throw ValidationException::withMessages(['body' => 'Yorum 2 ile 3000 karakter arasında olmalı.']);
        }

        $this->throttle($author);

        if ($replyTo !== null && ($replyTo->commentable_type !== $commentable->getMorphClass() || $replyTo->commentable_id !== $commentable->getKey())) {
            throw ValidationException::withMessages(['body' => 'Bu yoruma buradan cevap verilemez.']);
        }

        $comment = new Comment([
            'body' => $body,
            'parent_id' => $replyTo !== null ? ($replyTo->parent_id ?? $replyTo->id) : null,
            'approved_at' => $this->isTrusted($author) ? now() : null,
        ]);
        $comment->commentable()->associate($commentable);
        $comment->user()->associate($author);
        $comment->save();

        return $comment;
    }

    /**
     * Moderators, and members who already have an approved comment.
     */
    private function isTrusted(User $author): bool
    {
        return $author->can(Permission::ModerateComments->value)
            || $author->comments()->whereNotNull('approved_at')->exists();
    }

    private function throttle(User $author): void
    {
        foreach (['minute' => [self::PER_MINUTE, 60], 'day' => [self::PER_DAY, 86400]] as $window => [$limit, $seconds]) {
            $key = 'comments:'.$window.':'.$author->id;

            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw ValidationException::withMessages(['body' => 'Biraz yavaş: çok kısa sürede çok yorum yazdın. Biraz sonra tekrar dene.']);
            }

            RateLimiter::hit($key, $seconds);
        }
    }
}
