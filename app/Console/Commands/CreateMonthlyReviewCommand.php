<?php

namespace App\Console\Commands;

use App\Actions\Contact\SendContactMessage;
use App\Enums\Permission;
use App\Mail\MonthlyReviewReady;
use App\Models\MonthlyReview;
use App\Support\Push\PushNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[Signature('reviews:create {month? : The month as yyyy-mm, last month by default}')]
#[Description('Make the draft review of a month with its numbers frozen, and tell Kadir')]
class CreateMonthlyReviewCommand extends Command
{
    public function handle(PushNotifier $push): int
    {
        $argument = $this->argument('month');

        if (is_string($argument) && ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $argument)) {
            $this->error('The month must look like 2026-10.');

            return self::FAILURE;
        }

        $month = is_string($argument)
            ? CarbonImmutable::createFromFormat('!Y-m', $argument)
            : CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();

        if (MonthlyReview::query()->whereDate('month', $month->toDateString())->exists()) {
            $this->info('The review of '.$month->format('Y-m').' already exists.');

            return self::SUCCESS;
        }

        $review = MonthlyReview::makeFor($month);
        $review->save();

        $mail = new MonthlyReviewReady($review);
        $recipient = SendContactMessage::recipient();

        if ($recipient === '') {
            $this->warn('No address for Kadir: set MAIL_CONTACT_ADDRESS or legal.email.');
        } else {
            try {
                Mail::to($recipient)->send($mail);
            } catch (TransportExceptionInterface $exception) {
                // The draft stays; the admin dashboard shows it too.
                report($exception);
            }
        }

        $push->toPermitted(Permission::ManageGoals, [
            'title' => (string) $mail->envelope()->subject,
            'body' => 'Rakamlar hazır, gerisi sende.',
            'url' => route('admin.reviews.edit', $review),
            'tag' => 'review',
        ]);

        $this->info('Made the draft review of '.$month->format('Y-m').'.');

        return self::SUCCESS;
    }
}
