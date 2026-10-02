<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * The inner SVG markup of the Lucide icons in resources/icons/lucide.php.
 */
class LucideIcons
{
    /** @var array<string, string>|null */
    private static ?array $icons = null;

    public static function markup(string $name): string
    {
        self::$icons ??= require resource_path('icons/lucide.php');

        return self::$icons[$name]
            ?? throw new InvalidArgumentException("Unknown icon [{$name}]. Copy it from lucide.dev into resources/icons/lucide.php.");
    }
}
