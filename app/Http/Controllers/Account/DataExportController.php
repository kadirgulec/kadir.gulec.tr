<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\Post;
use App\Models\User;
use App\Support\Notifications\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Verilerimi indir": everything stored about the signed-in member as JSON
 * (GDPR art. 15 and 20). Secrets (password hash, 2FA, passkey keys) are left out.
 */
class DataExportController extends Controller
{
    public function __invoke(Request $request, Notifier $notifier): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'registered_at' => $user->created_at?->toIso8601String(),
                'roles' => $user->getRoleNames()->values()->all(),
                'notification_frequency' => $user->notification_frequency->value,
                'notify_new_posts' => $user->notify_new_posts,
                'notify_new_notes' => $user->notify_new_notes,
                'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'passkeys' => $user->passkeys()->get()->map(fn ($passkey): array => [
                    'name' => $passkey->name,
                    'created_at' => $passkey->created_at?->toIso8601String(),
                ])->all(),
            ],
            'comments' => $user->comments()->withTrashed()->with('commentable')->orderBy('created_at')->get()->map(fn (Comment $comment): array => [
                'body' => $comment->body,
                'post' => $comment->commentable instanceof Post ? ['title' => $comment->commentable->title, 'url' => route('posts.show', $comment->commentable->slug)] : null,
                'written_at' => $comment->created_at?->toIso8601String(),
                'approved' => $comment->isApproved(),
                'deleted' => $comment->trashed(),
            ])->all(),
            'follows' => $user->follows()->with('followable')->get()->map(fn (Follow $follow): array => [
                'type' => $follow->followable_type,
                'name' => $follow->followable instanceof Goal
                    ? $notifier->goalTitle($follow->followable, $user)
                    : ($follow->followable->title ?? $follow->followable->name ?? null),
                'since' => $follow->created_at?->toIso8601String(),
            ])->all(),
            'pending_notifications' => $user->notificationItems()->whereNull('sent_at')->get(['title', 'url', 'created_at'])->toArray(),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="kadir-gulec-tr-verilerim.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
