<?php

namespace App\Enums;

/**
 * Where one reading of a book stands. A book on the "currently reading" shelf
 * is being read or paused; finished and abandoned readings go to the diary.
 */
enum ReadingStatus: string
{
    case Reading = 'reading';
    case Paused = 'paused';
    case Finished = 'finished';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Okuyorum',
            self::Paused => 'Ara verdim',
            self::Finished => 'Bitirdim',
            self::Abandoned => 'Yarım bıraktım',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Reading => '📖',
            self::Paused => '⏸️',
            self::Finished => '✅',
            self::Abandoned => '🪦',
        };
    }

    /**
     * Whether the reading belongs on the "currently reading" shelf.
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
        return [self::Reading, self::Paused];
    }
}
