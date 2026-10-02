<?php

namespace App\Enums;

/**
 * How often a member gets e-mail about what they follow.
 */
enum NotificationFrequency: string
{
    case Instant = 'instant';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::Instant => 'Hemen (her olayda bir e-posta)',
            self::Daily => 'Günde bir özet',
            self::Weekly => 'Haftada bir özet (pazartesi)',
            self::Never => 'Hiç e-posta gönderme',
        };
    }
}
