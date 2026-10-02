<?php

namespace App\Models;

use App\Enums\ToolboxGroup;
use Carbon\CarbonImmutable;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A tool or language: on project cards and, with a toolbox group, as a
 * sticker in the toolbox on the about page.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ToolboxGroup|null $toolbox_group
 * @property int $toolbox_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'slug', 'toolbox_group', 'toolbox_order'])]
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Technology $technology): void {
            $technology->slug = Str::slug($technology->name) ?: Str::lower((string) Str::ulid());
        });
    }

    /**
     * The technologies with these names, created when missing (in this order).
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

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    protected function casts(): array
    {
        return [
            'toolbox_group' => ToolboxGroup::class,
        ];
    }
}
