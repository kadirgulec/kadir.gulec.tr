<?php

namespace App\Console\Commands;

use App\Actions\Contact\SendContactMessage;
use App\Enums\Permission;
use App\Mail\ChainReminder;
use App\Models\Goal;
use App\Support\ChainReminders;
use App\Support\Push\PushNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[Signature('chains:remind {--evening : Check the daily chains instead (run in the evening)}')]
#[Description('Mail Kadir the chains that need him today, each reminder once')]
class RemindChainsCommand extends Command
{
    public function handle(PushNotifier $push): int
    {
        $evening = (bool) $this->option('evening');
        $recipient = SendContactMessage::recipient();

        $entries = Goal::query()->activeChains()->with('chainDays')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Goal $chain): ?array => ($reminder = $evening ? ChainReminders::evening($chain) : ChainReminders::morning($chain))
                ? ['chain' => $chain, ...$reminder]
                : null)
            ->filter()
            ->reject(fn (array $entry): bool => DB::table('chain_reminders')->where('goal_id', $entry['chain']->id)->where('key', $entry['key'])->exists())
            ->values();

        if ($entries->isEmpty()) {
            return self::SUCCESS;
        }

        if ($recipient === '') {
            $this->error('No address for Kadir: set MAIL_CONTACT_ADDRESS or legal.email.');

            return self::FAILURE;
        }

        $mail = new ChainReminder(array_values($entries->all()));

        try {
            Mail::to($recipient)->send($mail);
        } catch (TransportExceptionInterface $exception) {
            // Not recorded: the reminder is tried again at the next run.
            report($exception);

            return self::FAILURE;
        }

        $push->toPermitted(Permission::ManageGoals, [
            'title' => (string) $mail->envelope()->subject,
            'body' => $entries->map(fn (array $entry): string => '🔥 '.$entry['chain']->title.': '.$entry['text'])->implode("\n"),
            'url' => route('admin.dashboard'),
            'tag' => 'chains',
        ]);

        DB::table('chain_reminders')->insertOrIgnore($entries->map(fn (array $entry): array => [
            'goal_id' => $entry['chain']->id,
            'key' => $entry['key'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return self::SUCCESS;
    }
}
