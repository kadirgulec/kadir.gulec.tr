<?php

namespace App\Http\Controllers\Account;

use App\Enums\NotificationFrequency;
use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\Goal;
use App\Models\Project;
use App\Models\User;
use App\Support\Notifications\Notifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The links in notification e-mails. They are signed, so they work without
 * signing in; GET shows a confirmation button (link scanners only GET),
 * POST does it (also the one-click unsubscribe of mail clients, RFC 8058).
 */
class UnsubscribeController extends Controller
{
    public function all(Request $request, User $user): View
    {
        if ($request->isMethod('POST')) {
            $user->forceFill(['notification_frequency' => NotificationFrequency::Never])->save();
            $user->notificationItems()->whereNull('sent_at')->delete();
        }

        return view('site.account.unsubscribe', [
            'done' => $request->isMethod('POST'),
            'heading' => 'Bütün e-postalar',
            'question' => 'Bundan sonra hiç bildirim e-postası almak istemiyor musun?',
            'doneText' => 'Tamam, bir daha bildirim e-postası göndermeyeceğim. Hesabımdan istediğin zaman geri açabilirsin.',
            'action' => $request->fullUrl(),
        ]);
    }

    public function follow(Request $request, Follow $follow): View
    {
        $name = $this->followableName($follow);

        if ($request->isMethod('POST')) {
            $follow->user->notificationItems()->whereNull('sent_at')
                ->where('subject_type', $follow->followable_type)
                ->where('subject_id', $follow->followable_id)
                ->delete();
            $follow->delete();
        }

        return view('site.account.unsubscribe', [
            'done' => $request->isMethod('POST'),
            'heading' => 'Takipten çık',
            'question' => '"'.$name.'" için e-posta almayı bırakmak istiyor musun?',
            'doneText' => 'Tamam, "'.$name.'" artık takip listende değil.',
            'action' => $request->fullUrl(),
        ]);
    }

    private function followableName(Follow $follow): string
    {
        $followable = $follow->followable;

        return match (true) {
            $followable instanceof Goal => app(Notifier::class)->goalTitle($followable, $follow->user),
            $followable instanceof Project => $followable->name,
            $followable !== null && isset($followable->title) => (string) $followable->title,
            default => 'bu kayıt',
        };
    }
}
