<?php

namespace App\Actions\Books;

use App\Models\Book;
use App\Support\Images\ImageStore;
use App\Support\Images\PosterPalette;
use Illuminate\Support\Facades\Storage;

/**
 * Stores a cover for a book and works out its accent color once (the same
 * palette as film posters). The previous cover's files are deleted.
 */
class StoreCover
{
    public function __construct(private ImageStore $images, private PosterPalette $palette) {}

    public function handle(Book $book, string $sourcePath): void
    {
        $oldCover = $book->cover_path;
        $base = $this->images->store($sourcePath, 'covers');
        $palette = $this->palette->fromFile(Storage::disk('public')->path(ImageStore::file($base, 480)));

        $book->forceFill([
            'cover_path' => $base,
            'accent' => $palette['accent'],
            'cover_colors' => $palette['colors'],
        ])->save();

        $this->images->delete($oldCover);
    }

    /**
     * Without a cover the drawn one still needs colors.
     */
    public function useAccent(Book $book, string $accent): void
    {
        $palette = $this->palette->palette($accent);

        $book->forceFill(['accent' => $palette['accent'], 'cover_colors' => $palette['colors']])->save();
    }
}
