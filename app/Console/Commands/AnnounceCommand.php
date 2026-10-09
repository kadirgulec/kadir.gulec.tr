<?php

namespace App\Console\Commands;

use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Note;
use App\Models\Post;
use App\Models\Watchable;
use App\Support\Notifications\Announcements;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:announce {--chain-breaks : Also look for chains missed yesterday (run once a day)}')]
#[Description('Tell followers about posts, notes, reviews and monthly reviews whose publication time has come')]
class AnnounceCommand extends Command
{
    public function handle(Announcements $announcements): int
    {
        Post::query()->published()->whereNull('announced_at')->each(function (Post $post) use ($announcements): void {
            $announcements->post($post);
            $post->forceFill(['announced_at' => now()])->saveQuietly();
        });

        Note::query()->published()->whereNull('announced_at')->with('tag')->each(function (Note $note) use ($announcements): void {
            $announcements->note($note);
            $note->forceFill(['announced_at' => now()])->saveQuietly();
        });

        MonthlyReview::query()->published()->whereNull('announced_at')->each(function (MonthlyReview $review) use ($announcements): void {
            $announcements->monthlyReview($review);
            $review->forceFill(['announced_at' => now()])->saveQuietly();
        });

        Watchable::query()->published()
            ->whereNotNull('review_html')
            ->whereNotNull('review_published_at')
            ->where('review_published_at', '<=', now())
            ->whereNull('review_announced_at')
            ->each(function (Watchable $watchable) use ($announcements): void {
                $announcements->review($watchable);
                $watchable->forceFill(['review_announced_at' => now()])->saveQuietly();
            });

        if ($this->option('chain-breaks')) {
            Goal::query()->activeChains()->visible()->with('chainDays')->each(fn (Goal $chain) => $announcements->chainBreak($chain));
        }

        return self::SUCCESS;
    }
}
