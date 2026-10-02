<?php

namespace App\Support\Images;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Stores uploaded images on the public disk as WebP in a few widths.
 *
 * Every image is re-encoded, which rotates it by its EXIF orientation and
 * drops all metadata (camera, location). Models keep only the base path
 * ("projects/01j…"); the files are "{base}-480.webp", "-960" and "-1600".
 * Images are never scaled up.
 */
class ImageStore
{
    public const WIDTHS = [480, 960, 1600];

    private const QUALITY = 82;

    /**
     * @param  string  $sourcePath  A readable local file (an upload's real path).
     * @return string The base path to store on the model.
     */
    public function store(string $sourcePath, string $directory): string
    {
        $base = trim($directory, '/').'/'.Str::lower((string) Str::ulid());
        $manager = ImageManager::usingDriver(Driver::class);

        foreach (self::WIDTHS as $width) {
            $image = $manager->decodePath($sourcePath);
            $image->orient();
            $image->scaleDown(width: $width);

            $this->disk()->put(self::file($base, $width), $image->encodeUsingFormat(Format::WEBP, quality: self::QUALITY)->toString());
        }

        return $base;
    }

    public function delete(?string $base): void
    {
        if (blank($base)) {
            return;
        }

        $this->disk()->delete(array_map(fn (int $width): string => self::file($base, $width), self::WIDTHS));
    }

    public static function url(?string $base, int $width = 960): ?string
    {
        if (blank($base)) {
            return null;
        }

        return Storage::disk('public')->url(self::file($base, $width));
    }

    /**
     * The srcset attribute value of all stored widths.
     */
    public static function srcset(?string $base): ?string
    {
        if (blank($base)) {
            return null;
        }

        return implode(', ', array_map(fn (int $width): string => self::url($base, $width).' '.$width.'w', self::WIDTHS));
    }

    public static function file(string $base, int $width): string
    {
        return $base.'-'.$width.'.webp';
    }

    private function disk(): Filesystem
    {
        return Storage::disk('public');
    }
}
