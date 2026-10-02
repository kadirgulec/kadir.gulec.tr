<?php

namespace App\Support;

use App\Enums\GoalVisibility;

/**
 * Prepares goals for visitors. Censored goals never send their words to the
 * browser, only how long they are; otherwise anyone could read the
 * blacked-out text in the page source. Numbers and shapes (progress, chain
 * links, milestone count) stay.
 */
class GoalCensor
{
    /**
     * @param  array{visibility: GoalVisibility, title: string, why?: mixed, milestones?: list<array{title: string, done: bool}>, updates?: list<array<string, mixed>>, progressNotes?: list<array<string, mixed>>, ...}  $goal
     * @return array<string, mixed>
     */
    public static function apply(array $goal): array
    {
        if ($goal['visibility'] !== GoalVisibility::Censored) {
            return [...$goal, 'titleLength' => null, 'whyLength' => null];
        }

        return [
            ...$goal,
            'title' => null,
            'titleLength' => mb_strlen($goal['title']),
            'why' => null,
            'whyLength' => isset($goal['why']) ? mb_strlen(strip_tags((string) $goal['why'])) : null,
            'milestones' => array_map(
                fn (array $milestone): array => [...$milestone, 'title' => null],
                $goal['milestones'] ?? [],
            ),
            'updates' => [],
            'progressNotes' => [],
            // A picture or a linked project could give the topic away as well.
            'imageUrl' => null,
            'linkUrl' => null,
        ];
    }
}
