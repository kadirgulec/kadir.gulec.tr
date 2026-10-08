<?php

namespace App\Enums;

/**
 * The three floors of the goals page.
 */
enum GoalKind: string
{
    case Chain = 'chain';
    case Yearly = 'yearly';
    case LongTerm = 'long-term';

    public function label(): string
    {
        return match ($this) {
            self::Chain => 'Zincir',
            self::Yearly => 'Yıllık hedef',
            self::LongTerm => 'Uzun vadeli hedef',
        };
    }

    /**
     * The URL value of ?tur= on the admin create page.
     */
    public function routeSegment(): string
    {
        return match ($this) {
            self::Chain => 'zincir',
            self::Yearly => 'yillik',
            self::LongTerm => 'uzun-vade',
        };
    }

    public static function fromRouteSegment(string $segment): ?self
    {
        return array_find(self::cases(), fn (self $kind): bool => $kind->routeSegment() === $segment);
    }
}
