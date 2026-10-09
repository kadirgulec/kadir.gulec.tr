<?php

namespace App\Support\Reviews;

use App\Enums\ChainPeriod;
use App\Enums\ReviewItemKind;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Post;

/**
 * Lines Kadir may take into a review, read from its frozen numbers: chains
 * that held or slipped, numeric goals ahead or behind, the month's writing.
 * Only for the admin: titles are written in full, censored goals included.
 * Nothing is suggested for "try": what to try is his own call.
 */
class ReviewSuggestions
{
    /** A chain that held at least this share of its links went well… */
    private const HELD_WELL = 90;

    /** …and one below this share was hard. */
    private const HELD_POORLY = 50;

    /**
     * @return list<array{kind: ReviewItemKind, text: string}>
     */
    public function for(MonthlyReview $review): array
    {
        $stats = $review->stats;
        $goalIds = [...array_column($stats['chains'], 'goal_id'), ...array_column($stats['yearly'], 'goal_id')];
        $goals = Goal::query()->whereKey($goalIds)->pluck('title', 'id');
        $suggestions = [];

        foreach ($stats['chains'] as $chain) {
            $title = $goals[$chain['goal_id']] ?? null;
            $unit = ChainPeriod::from($chain['period'])->unit();

            if ($title === null || $chain['links'] === 0) {
                continue;
            }

            if ($chain['record'] !== null) {
                $suggestions[] = $this->good($title.': yeni rekor seri, '.$chain['record'].' '.$unit);
            }

            if ($chain['successRate'] >= self::HELD_WELL) {
                $suggestions[] = $this->good($title.': '.$chain['held'].' / '.$chain['links'].' '.$unit.' tuttu');
            } elseif ($chain['successRate'] < self::HELD_POORLY) {
                $suggestions[] = $this->hard($title.': sadece '.$chain['held'].' / '.$chain['links'].' '.$unit.' tuttu');
            }
        }

        foreach ($stats['yearly'] as $goal) {
            $title = $goals[$goal['goal_id']] ?? null;
            $standing = trim($goal['current'].' / '.$goal['target'].' '.$goal['unit']);

            if ($title === null) {
                continue;
            }

            match ($goal['pace']) {
                'ahead' => $suggestions[] = $this->good($title.': önde ('.$standing.')'),
                'behind' => $suggestions[] = $this->hard($title.': biraz geride ('.$standing.')'),
                default => null,
            };
        }

        $posts = $stats['published']['posts'];

        if ($posts === 0) {
            $suggestions[] = $this->hard('Bu ay hiç yazı yayınlanmadı');
        } elseif ($posts >= 2) {
            $suggestions[] = $this->good($posts.' yazı yayınlandı');
        }

        $topPost = $stats['visitors']['topPost'];
        $topTitle = $topPost !== null ? Post::query()->whereKey($topPost['post_id'])->value('title') : null;

        if ($topTitle !== null) {
            $suggestions[] = $this->good('"'.$topTitle.'" ayın en çok okunan yazısı oldu');
        }

        return $suggestions;
    }

    /**
     * @return array{kind: ReviewItemKind, text: string}
     */
    private function good(string $text): array
    {
        return ['kind' => ReviewItemKind::Good, 'text' => $text];
    }

    /**
     * @return array{kind: ReviewItemKind, text: string}
     */
    private function hard(string $text): array
    {
        return ['kind' => ReviewItemKind::Hard, 'text' => $text];
    }
}
