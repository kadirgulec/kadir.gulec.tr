<?php

namespace App\Enums;

enum SeriesStatus: string
{
    case Watching = 'watching';
    case Paused = 'paused';
    case Finished = 'finished';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Watching => 'İzliyorum',
            self::Paused => 'Ara verdim',
            self::Finished => 'Bitirdim',
            self::Dropped => 'Bıraktım',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Watching => '📺',
            self::Paused => '⏸️',
            self::Finished => '✅',
            self::Dropped => '🪦',
        };
    }

    /**
     * Whether the series belongs on the "currently watching" shelf.
     */
    public function isInProgress(): bool
    {
        return in_array($this, self::inProgress(), true);
    }

    /**
     * @return list<self>
     */
    public static function inProgress(): array
    {
        return [self::Watching, self::Paused];
    }
}
