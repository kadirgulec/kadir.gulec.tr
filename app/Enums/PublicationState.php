<?php

namespace App\Enums;

/**
 * Derived from published_at: empty is a draft, in the future is scheduled,
 * in the past is published.
 */
enum PublicationState: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::Scheduled => 'Zamanlanmış',
            self::Published => 'Yayında',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Scheduled => 'yellow',
            self::Published => 'green',
        };
    }
}
