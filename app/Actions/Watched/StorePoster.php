<?php

namespace App\Actions\Watched;

use App\Models\Watchable;
use App\Support\Images\ImageStore;
use App\Support\Images\PosterPalette;
use Illuminate\Support\Facades\Storage;

/**
 * Stores a poster for a film or series and works out its accent color once.
 * The previous poster's files are deleted.
 */
class StorePoster
{
    public function __construct(private ImageStore $images, private PosterPalette $palette) {}

    public function handle(Watchable $watchable, string $sourcePath): void
    {
        $oldPoster = $watchable->poster_path;
        $base = $this->images->store($sourcePath, 'posters');
        $palette = $this->palette->fromFile(Storage::disk('public')->path(ImageStore::file($base, 480)));

        $watchable->forceFill([
            'poster_path' => $base,
            'accent' => $palette['accent'],
            'poster_colors' => $palette['colors'],
        ])->save();

        $this->images->delete($oldPoster);
    }

    /**
     * Without a poster the drawn fallback still needs colors.
     */
    public function useAccent(Watchable $watchable, string $accent): void
    {
        $palette = $this->palette->palette($accent);

        $watchable->forceFill(['accent' => $palette['accent'], 'poster_colors' => $palette['colors']])->save();
    }
}
