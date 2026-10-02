<?php

namespace App\Enums;

enum WatchableType: string
{
    case Film = 'film';
    case Series = 'series';

    public function label(): string
    {
        return match ($this) {
            self::Film => 'Film',
            self::Series => 'Dizi',
        };
    }

    /**
     * The Turkish URL segment, e.g. /izlediklerim/dizi/severance.
     */
    public function routeSegment(): string
    {
        return match ($this) {
            self::Film => 'film',
            self::Series => 'dizi',
        };
    }

    public static function fromRouteSegment(string $segment): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->routeSegment() === $segment) {
                return $type;
            }
        }

        return null;
    }
}
