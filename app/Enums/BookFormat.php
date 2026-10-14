<?php

namespace App\Enums;

/**
 * How a book was read. Most books are e-books (Kindle), so that is the default.
 */
enum BookFormat: string
{
    case Ebook = 'ebook';
    case Print = 'print';
    case Audio = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::Ebook => 'E-kitap',
            self::Print => 'Basılı',
            self::Audio => 'Sesli kitap',
        };
    }
}
