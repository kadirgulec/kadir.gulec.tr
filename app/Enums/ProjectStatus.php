<?php

namespace App\Enums;

/**
 * The stamp on a project: under construction, live or archived.
 */
enum ProjectStatus: string
{
    case InProgress = 'in-progress';
    case Live = 'live';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Yapım aşamasında',
            self::Live => 'Yayında',
            self::Archived => 'Arşiv',
        };
    }

    /**
     * Badge color in the admin panel.
     */
    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'yellow',
            self::Live => 'green',
            self::Archived => 'zinc',
        };
    }
}
