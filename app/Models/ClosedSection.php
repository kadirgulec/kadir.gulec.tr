<?php

namespace App\Models;

use App\Enums\Section;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * A section Kadir closed from the admin panel: gone from the tabs and the
 * other pages, a 404 for visitors (see Section::isOpen()). Only closable
 * sections get a row (Section::isClosable()).
 *
 * @property Section $section
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['section'])]
class ClosedSection extends Model
{
    private const CACHE_KEY = 'closed-sections';

    protected $primaryKey = 'section';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The closed sections. Every page asks several times (tabs, mobile bar,
     * layout), so the answer is cached, and memoized within the request.
     *
     * @return list<Section>
     */
    public static function sections(): array
    {
        try {
            /** @var list<string> $values */
            $values = Cache::memo()->rememberForever(self::CACHE_KEY, fn (): array => static::query()->toBase()->pluck('section')->all());
        } catch (Throwable) {
            // Error pages must render even when the database does not.
            return [];
        }

        return array_values(array_filter(array_map(fn (string $value): ?Section => Section::tryFrom($value), $values)));
    }

    public static function close(Section $section): void
    {
        static::query()->firstOrCreate(['section' => $section->value]);
        Cache::memo()->forget(self::CACHE_KEY);
    }

    public static function reopen(Section $section): void
    {
        static::query()->whereKey($section->value)->delete();
        Cache::memo()->forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => Section::class,
        ];
    }
}
