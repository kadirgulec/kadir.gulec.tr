<?php

namespace App\Actions\Comments;

use App\Models\Comment;

/**
 * Kadir's side of comments: approve a waiting comment, or delete one.
 * A deleted comment with replies stays as "silindi" so the thread holds.
 */
class ModerateComment
{
    public function approve(Comment $comment): void
    {
        $comment->forceFill(['approved_at' => now()])->save();
    }

    public function delete(Comment $comment): void
    {
        if ($comment->replies()->exists()) {
            $comment->delete();

            return;
        }

        $comment->forceDelete();
    }
}
