<?php

namespace App\Support\Notifications;

use App\Enums\GoalVisibility;
use App\Enums\Permission;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\NotificationItem;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Turns something that happened into notification items, one per member.
 * Members who get no e-mail (unverified, blocked, "never") get no items;
 * hidden goals tell nobody anything. The message is written per member,
 * so a censored goal keeps its secret from members who may not read it.
 */
class Notifier
{
    /**
     * @param  Closure(User): array{title: string, body?: ?string, url: string}  $message
     */
    public function toFollowers(Model $subject, string $key, Closure $message): void
    {
        if ($subject instanceof Goal && $subject->visibility === GoalVisibility::Hidden) {
            return;
        }

        $follows = Follow::query()
            ->where('followable_type', $subject->getMorphClass())
            ->where('followable_id', $subject->getKey())
            ->with('user')
            ->get();

        foreach ($follows as $follow) {
            $this->toUser($follow->user, $subject, $key, $message($follow->user));
        }
    }

    /**
     * @param  array{title: string, body?: ?string, url: string}  $message
     */
    public function toUser(User $user, ?Model $subject, string $key, array $message): void
    {
        if (! $user->receivesNotifications()) {
            return;
        }

        $item = NotificationItem::query()->firstOrNew([
            'user_id' => $user->id,
            'key' => ($subject !== null ? $subject->getMorphClass().':'.$subject->getKey().':' : '').$key,
        ]);

        if ($item->exists) {
            return;
        }

        $item->fill(['title' => $message['title'], 'body' => $message['body'] ?? null, 'url' => $message['url']]);

        if ($subject !== null) {
            $item->subject()->associate($subject);
        }

        $item->save();
    }

    /**
     * A goal's title as this member may read it.
     */
    public function goalTitle(Goal $goal, User $user): string
    {
        return $goal->visibility === GoalVisibility::Censored && ! Gate::forUser($user)->allows(Permission::ViewCensoredGoals->value)
            ? '🔒 ██████'
            : $goal->title;
    }

    /**
     * A goal's address as this member may see it (see GoalContent::slugFor()).
     */
    public function goalSlug(Goal $goal, User $user): string
    {
        return $goal->visibility === GoalVisibility::Censored && ! Gate::forUser($user)->allows(Permission::ViewCensoredGoals->value)
            ? 'k-'.$goal->id
            : $goal->slug;
    }
}
