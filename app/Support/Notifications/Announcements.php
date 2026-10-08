<?php

namespace App\Support\Notifications;

use App\Enums\ChainDayState;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Models\ChainDay;
use App\Models\Comment;
use App\Models\DevlogEntry;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\GoalProgress;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\Season;
use App\Models\User;
use App\Models\Viewing;
use App\Models\Watchable;
use App\Support\ChainStats;
use Carbon\CarbonImmutable;

/**
 * The moments followers hear about, wired to model events in register().
 * Scheduled publications (posts, reviews) and broken chains are found by
 * the notifications:announce command instead.
 */
class Announcements
{
    public function __construct(private Notifier $notifier) {}

    public static function register(): void
    {
        $announce = fn (): self => app(self::class);

        Viewing::created(fn (Viewing $viewing) => $announce()->viewing($viewing));
        Season::saved(fn (Season $season) => $season->wasChanged('note') || ($season->wasRecentlyCreated && filled($season->note)) ? $announce()->seasonNote($season) : null);
        Watchable::updated(fn (Watchable $watchable) => $watchable->wasChanged('series_status') ? $announce()->seriesStatus($watchable) : null);
        Project::updated(fn (Project $project) => $project->wasChanged('status') ? $announce()->projectStatus($project) : null);
        DevlogEntry::created(fn (DevlogEntry $entry) => $announce()->logEntry($entry));
        GoalProgress::created(fn (GoalProgress $progress) => $announce()->progress($progress));
        GoalMilestone::updated(fn (GoalMilestone $milestone) => $milestone->wasChanged('done_at') && $milestone->done_at !== null ? $announce()->milestone($milestone) : null);
        Goal::updated(fn (Goal $goal) => $goal->wasChanged('achieved_at') && $goal->achieved_at !== null ? $announce()->achieved($goal) : null);
        ChainDay::saved(fn (ChainDay $day) => $day->state === ChainDayState::Done ? $announce()->chainDay($day) : null);
        Comment::saved(fn (Comment $comment) => $comment->approved_at !== null && $comment->wasChanged('approved_at') || ($comment->wasRecentlyCreated && $comment->approved_at !== null) ? $announce()->reply($comment) : null);
    }

    public function viewing(Viewing $viewing): void
    {
        $watchable = $viewing->watchable;

        if (! $watchable->isPublished()) {
            return;
        }

        $this->notifier->toFollowers($watchable, 'viewing-'.$viewing->id, fn (User $user): array => [
            'title' => $watchable->title.': '.($viewing->note ?? 'yeniden izledim'),
            'url' => url($watchable->publicPath()),
        ]);
    }

    public function seasonNote(Season $season): void
    {
        $watchable = $season->watchable;

        if (! $watchable->isPublished() || blank($season->note)) {
            return;
        }

        $this->notifier->toFollowers($watchable, 'season-note-'.$season->id.'-'.md5((string) $season->note), fn (User $user): array => [
            'title' => $watchable->title.', '.$season->number.'. sezon: yeni not',
            'body' => $season->note,
            'url' => url($watchable->publicPath()).'#sezonlar',
        ]);
    }

    public function seriesStatus(Watchable $watchable): void
    {
        if (! $watchable->isPublished() || $watchable->series_status === null) {
            return;
        }

        $status = $watchable->series_status;

        $this->notifier->toFollowers($watchable, 'status-'.$status->value.'-'.today()->toDateString(), fn (User $user): array => [
            'title' => $watchable->title.': '.$status->emoji().' '.mb_strtolower($status->label()),
            'url' => url($watchable->publicPath()),
        ]);
    }

    /**
     * A review whose publication time passed (called by the announce command).
     */
    public function review(Watchable $watchable): void
    {
        $this->notifier->toFollowers($watchable, 'review', fn (User $user): array => [
            'title' => $watchable->title.' hakkında yorum yazdım',
            'url' => url($watchable->publicPath()).'#yorumum',
        ]);
    }

    /**
     * A post whose publication time passed (called by the announce command).
     */
    public function post(Post $post): void
    {
        User::query()->where('notify_new_posts', true)->each(fn (User $user) => $this->notifier->toUser($user, $post, 'published', [
            'title' => 'Yeni yazı: '.$post->title,
            'body' => $post->excerptText(),
            'url' => route('posts.show', $post->slug),
        ]));
    }

    /**
     * A note whose publication time passed (called by the announce command).
     * Notes are small and frequent, so they only ever go out in the digest.
     */
    public function note(Note $note): void
    {
        $text = $note->text();

        User::query()->where('notify_new_notes', true)->each(fn (User $user) => $this->notifier->toUser($user, $note, 'published', [
            'title' => 'Yeni not: #'.$note->tag->name,
            'body' => $text,
            'url' => route('notes.show', $note->id),
        ], digestOnly: true));
    }

    public function projectStatus(Project $project): void
    {
        if (! $project->isPublished()) {
            return;
        }

        $this->notifier->toFollowers($project, 'status-'.$project->status->value, fn (User $user): array => [
            'title' => $project->name.': '.mb_strtolower($project->status->label()),
            'url' => route('projects.show', $project->slug),
        ]);
    }

    public function logEntry(DevlogEntry $entry): void
    {
        $owner = $entry->loggable;

        if ($owner instanceof Project && $owner->isPublished()) {
            $this->notifier->toFollowers($owner, 'devlog-'.$entry->id, fn (User $user): array => [
                'title' => $owner->name.': yeni geliştirme notu',
                'body' => $entry->body,
                'url' => route('projects.show', $owner->slug).'#devlog',
            ]);
        }

        if ($owner instanceof Goal && $owner->kind === GoalKind::LongTerm) {
            $this->notifier->toFollowers($owner, 'update-'.$entry->id, fn (User $user): array => [
                'title' => $this->notifier->goalTitle($owner, $user).': yeni güncelleme',
                'body' => $this->notifier->goalTitle($owner, $user) === $owner->title ? $entry->body : null,
                'url' => $this->goalUrl($owner, $user),
            ]);
        }
    }

    public function progress(GoalProgress $progress): void
    {
        $goal = $progress->goal;

        if ($goal->measure !== GoalMeasure::Numeric || ! $goal->target) {
            return;
        }

        $after = $goal->current();
        $before = $after - $progress->amount;

        if ($before * 2 < $goal->target && $after * 2 >= $goal->target && $after < $goal->target) {
            $this->notifier->toFollowers($goal, 'half', fn (User $user): array => [
                'title' => $this->notifier->goalTitle($goal, $user).': yarısı tamam ('.$after.' / '.$goal->target.')',
                'url' => $this->goalUrl($goal, $user),
            ]);
        }

        if ($before < $goal->target && $after >= $goal->target) {
            $this->achieved($goal);
        }
    }

    public function milestone(GoalMilestone $milestone): void
    {
        $goal = $milestone->goal;

        $this->notifier->toFollowers($goal, 'milestone-'.$milestone->id, fn (User $user): array => [
            'title' => $this->notifier->goalTitle($goal, $user).': bir kilometre taşı geçildi',
            'body' => $this->notifier->goalTitle($goal, $user) === $goal->title ? $milestone->title : null,
            'url' => $this->goalUrl($goal, $user),
        ]);

        if ($goal->fresh(['milestones'])?->isAchieved()) {
            $this->achieved($goal);
        }
    }

    public function achieved(Goal $goal): void
    {
        $this->notifier->toFollowers($goal, 'achieved', fn (User $user): array => [
            'title' => $this->notifier->goalTitle($goal, $user).': BAŞARILDI',
            'url' => $this->goalUrl($goal, $user),
        ]);
    }

    /**
     * A marked day may complete a link, and that a round streak or a new record (once per run).
     * The counts are in the chain's own links: 10 weeks, 6 months.
     */
    public function chainDay(ChainDay $day): void
    {
        $chain = $day->goal->load('chainDays');
        $period = $chain->chain_period;
        $links = $chain->chainLinks();
        $states = array_column($links, 'state');
        $streak = ChainStats::currentStreak($states);
        $runStart = $this->runStart($links)?->toDateString() ?? 'start';

        if (in_array($streak, $period->milestones(), true)) {
            $this->notifier->toFollowers($chain, 'streak-'.$streak.'-'.$runStart, fn (User $user): array => [
                'title' => $this->notifier->goalTitle($chain, $user).': 🔥 '.$streak.' '.$period->unit().'!',
                'url' => $this->goalUrl($chain, $user),
            ]);
        }

        $bestBefore = ChainStats::bestStreak(array_slice($states, 0, max(0, count($states) - $streak)));

        if ($streak > $bestBefore && $bestBefore >= $period->recordMinimum()) {
            $this->notifier->toFollowers($chain, 'record-'.$runStart, fn (User $user): array => [
                'title' => $this->notifier->goalTitle($chain, $user).': yeni rekor seri ('.$period->accusative($bestBefore).' geçti)',
                'url' => $this->goalUrl($chain, $user),
            ]);
        }
    }

    /**
     * A chain whose link ended yesterday (a day, a week, a month) and did not hold,
     * after a streak of at least three links (called daily).
     */
    public function chainBreak(Goal $chain): void
    {
        $yesterday = CarbonImmutable::yesterday()->toDateString();
        $links = array_values(array_filter(
            $chain->chainLinks(),
            fn (array $link): bool => $link['end']->toDateString() <= $yesterday,
        ));
        $last = end($links);

        if ($last === false || $last['state'] !== 'missed' || $last['end']->toDateString() !== $yesterday) {
            return;
        }

        $lost = ChainStats::currentStreak(array_slice(array_column($links, 'state'), 0, -1));

        if ($lost < 3) {
            return;
        }

        $this->notifier->toFollowers($chain, 'break-'.$yesterday, fn (User $user): array => [
            'title' => $this->notifier->goalTitle($chain, $user).': zincir koptu ('.$lost.' '.$chain->chain_period->unit().' sürdü)',
            'url' => $this->goalUrl($chain, $user),
        ]);
    }

    public function reply(Comment $comment): void
    {
        $parent = $comment->parent;
        $post = $comment->commentable;

        if ($parent?->user === null || $parent->user_id === $comment->user_id || ! $post instanceof Post) {
            return;
        }

        $this->notifier->toUser($parent->user, $post, 'reply-'.$comment->id, [
            'title' => $comment->authorName().' yorumuna cevap verdi: '.$post->title,
            'body' => $comment->body,
            'url' => route('posts.show', $post->slug).'#yorumlar',
        ]);
    }

    private function goalUrl(Goal $goal, User $user): string
    {
        return url($goal->publicPath($goal->slugFor($user)));
    }

    /**
     * @param  list<array{start: CarbonImmutable, state: string}>  $links
     */
    private function runStart(array $links): ?CarbonImmutable
    {
        $start = null;

        foreach (array_reverse($links) as $link) {
            if ($link['state'] === 'missed') {
                break;
            }

            $start = $link['start'];
        }

        return $start;
    }
}
