<?php

namespace App\Enums;

/**
 * Who sees a goal. Chosen per goal in the admin panel later.
 */
enum GoalVisibility: string
{
    /** Shown as is. */
    case Public = 'public';

    /** The card stays, but its words are blacked out; progress remains visible. */
    case Censored = 'censored';

    /** Never shown to visitors. */
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Açık',
            self::Censored => 'Sansürlü',
            self::Hidden => 'Gizli',
        };
    }

    public function isVisible(): bool
    {
        return $this !== self::Hidden;
    }
}
