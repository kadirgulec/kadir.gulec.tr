<?php

namespace App\Support;

use App\Enums\GoalVisibility;

/**
 * Prepares goals for visitors. Censored goals never send their words to the
 * browser, only how long they are; otherwise anyone could read the
 * blacked-out text in the page source.
 */
class GoalCensor
{
    /**
     * @param  array{visibility: GoalVisibility, title: string, why?: string, milestones?: list<array{title: string, done: bool}>}  $goal
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
            'whyLength' => isset($goal['why']) ? mb_strlen($goal['why']) : null,
            'milestones' => array_map(
                fn (array $milestone): array => [...$milestone, 'title' => null],
                $goal['milestones'] ?? [],
            ),
        ];
    }
}
