<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A permanent (301) redirect from an old address to the current one.
 *
 * @property int $id
 * @property string $from_path
 * @property string $to_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['from_path', 'to_path'])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    /**
     * Sends $from to $to. Older redirects that pointed at $from follow along,
     * so there is never a chain, and a redirect away from $to is dropped
     * (the address is in use again).
     */
    public static function point(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        static::query()->where('from_path', $to)->delete();
        static::query()->where('to_path', $from)->update(['to_path' => $to]);
        static::query()->updateOrCreate(['from_path' => $from], ['to_path' => $to]);
    }

    public static function target(string $path): ?string
    {
        return static::query()->where('from_path', '/'.ltrim($path, '/'))->value('to_path');
    }
}
