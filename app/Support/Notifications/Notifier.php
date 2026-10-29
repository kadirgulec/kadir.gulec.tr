<?php

namespace App\Support\Notifications;

use App\Enums\Section;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\NotificationItem;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns something that happened into notification items, one per member.
 * Members who get no e-mail (unverified, blocked, "never") get no items;
 * hidden goals tell nobody anything, and neither do goals and monthly
 * reviews while the Goals section is closed (Kadir's own chain reminders
 * and review mails do not come through here). The message is written per
 * member, so a censored goal keeps its secret from members who may not read it.
 */
class Notifier
{
    /**
     * @param  Closure(User): array{title: string, body?: ?string, url: string}  $message
     * @param  bool  $digestOnly  Wait for the daily or weekly digest even when the member wants e-mails at once.
     */
    public function toFollowers(Model $subject, string $key, Closure $message, bool $digestOnly = false): void
    {
        if (($subject instanceof Goal && ! $subject->visibility->isVisible()) || $this->isInClosedSection($subject)) {
            return;
        }

        $follows = Follow::query()
            ->where('followable_type', $subject->getMorphClass())
            ->where('followable_id', $subject->getKey())
            ->with('user')
            ->get();

        foreach ($follows as $follow) {
            $this->toUser($follow->user, $subject, $key, $message($follow->user), $digestOnly);
        }
    }

    /**
     * @param  array{title: string, body?: ?string, url: string}  $message
     * @param  bool  $digestOnly  Wait for the daily or weekly digest even when the member wants e-mails at once.
     */
    public function toUser(User $user, ?Model $subject, string $key, array $message, bool $digestOnly = false): void
    {
        if (! $user->receivesNotifications() || $this->isInClosedSection($subject)) {
            return;
        }

        $item = NotificationItem::query()->firstOrNew([
            'user_id' => $user->id,
            'key' => ($subject !== null ? $subject->getMorphClass().':'.$subject->getKey().':' : '').$key,
        ]);

        if ($item->exists) {
            return;
        }

        $item->fill(['title' => $message['title'], 'body' => $message['body'] ?? null, 'url' => $message['url'], 'digest_only' => $digestOnly]);

        if ($subject !== null) {
            $item->subject()->associate($subject);
        }

        $item->save();
    }

    /**
     * Goals and monthly reviews belong to the Goals section.
     */
    private function isInClosedSection(?Model $subject): bool
    {
        return ($subject instanceof Goal || $subject instanceof MonthlyReview) && ! Section::Goals->isOpen();
    }

    /**
     * A goal's title as this member may read it.
     */
    public function goalTitle(Goal $goal, User $user): string
    {
        return $goal->isCensoredFor($user) ? '🔒 ██████' : $goal->title;
    }
}
