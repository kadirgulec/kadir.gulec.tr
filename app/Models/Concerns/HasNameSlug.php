<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A lookup row (a tag, a technology) found by its name, with a slug that
 * always follows the name.
 *
 * @property string $name
 * @property string $slug
 */
trait HasNameSlug
{
    public static function bootHasNameSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            $model->slug = Str::slug($model->name) ?: Str::lower((string) Str::ulid());
        });
    }

    /**
     * The rows with these names, created when missing (in this order).
     *
     * @param  list<string>  $names
     * @return list<int>
     */
    public static function idsForNames(array $names): array
    {
        $ids = [];

        foreach (array_values(array_unique(array_filter(array_map('trim', $names)))) as $name) {
            $ids[] = (int) static::query()->firstOrCreate(['name' => $name])->getKey();
        }

        return $ids;
    }
}
