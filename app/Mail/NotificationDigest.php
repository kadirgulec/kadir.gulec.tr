<?php

namespace App\Mail;

use App\Models\Follow;
use App\Models\NotificationItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * One e-mail with everything that happened in what a member follows.
 * Every message carries a one-click unsubscribe (RFC 8058) for all e-mail,
 * and every item an "unfollow this" link; both work without signing in.
 */
class NotificationDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, NotificationItem>  $items
     */
    public function __construct(public User $user, public Collection $items) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->items->count() === 1
                ? (string) $this->items->first()?->title
                : 'Defterde '.$this->items->count().' yeni şey',
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.notification-digest',
            text: 'mail.notification-digest-text',
            with: [
                'entries' => $this->items->map(fn (NotificationItem $item): array => [
                    'item' => $item,
                    'unfollowUrl' => $this->unfollowUrl($item),
                ])->all(),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
                'settingsUrl' => route('notifications.edit'),
            ],
        );
    }

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('notifications.unsubscribe', ['user' => $this->user->id]);
    }

    private function unfollowUrl(NotificationItem $item): ?string
    {
        if ($item->subject_type === null) {
            return null;
        }

        $follow = Follow::query()
            ->where('user_id', $this->user->id)
            ->where('followable_type', $item->subject_type)
            ->where('followable_id', $item->subject_id)
            ->first();

        return $follow ? URL::signedRoute('follows.unsubscribe', ['follow' => $follow->id]) : null;
    }
}
