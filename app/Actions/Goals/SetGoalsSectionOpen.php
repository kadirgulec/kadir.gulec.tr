<?php

namespace App\Actions\Goals;

use App\Enums\Section;
use App\Models\ClosedSection;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\NotificationItem;
use Illuminate\Support\Facades\DB;

/**
 * Opens or closes the Goals section on the site (the switch on /admin/hedefler).
 * Closing also drops what followers were still waiting to hear about goals and
 * monthly reviews, so no e-mail leads to a page that is gone. Nothing is sent
 * later on reopening: what happened in between stays untold.
 */
class SetGoalsSectionOpen
{
    public function handle(bool $open): void
    {
        if ($open) {
            ClosedSection::reopen(Section::Goals);

            return;
        }

        DB::transaction(function (): void {
            ClosedSection::close(Section::Goals);

            NotificationItem::query()
                ->whereNull('sent_at')
                ->whereIn('subject_type', [(new Goal)->getMorphClass(), (new MonthlyReview)->getMorphClass()])
                ->delete();
        });
    }
}
