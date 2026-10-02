<?php

namespace App\Enums;

/**
 * Where a technology sits in the toolbox on the about page.
 */
enum ToolboxGroup: string
{
    case Daily = 'daily';
    case Sometimes = 'sometimes';
    case Languages = 'languages';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Her gün',
            self::Sometimes => 'Ara sıra',
            self::Languages => 'Diller',
        };
    }
}
