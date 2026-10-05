<?php

namespace App\Models;

use App\Enums\ToolboxGroup;
use Carbon\CarbonImmutable;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
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
     * Puts the technology at the end of a toolbox group, or takes it out of
     * the toolbox (null). It stays on its projects either way.
     */
    public function placeInToolbox(?ToolboxGroup $group): void
    {
        $last = $group === null ? null : static::query()
            ->where('toolbox_group', $group)
            ->whereKeyNot($this->getKey())
            ->max('toolbox_order');

        $this->toolbox_group = $group;
        $this->toolbox_order = $group === null ? 0 : (int) ($last ?? -1) + 1;
        $this->save();
    }

    /**
     * Moves the technology to a zero-based position within its toolbox group
     * and renumbers the group.
     */
    public function moveInToolbox(int $position): void
    {
        if ($this->toolbox_group === null) {
            return;
        }

        DB::transaction(function () use ($position): void {
            $ids = static::query()
                ->where('toolbox_group', $this->toolbox_group)
                ->whereKeyNot($this->getKey())
                ->orderBy('toolbox_order')
                ->orderBy('name')
                ->pluck('id')
                ->all();

            array_splice($ids, max(0, min($position, count($ids))), 0, [$this->getKey()]);

            foreach ($ids as $order => $id) {
                static::query()->whereKey($id)->update(['toolbox_order' => $order]);
            }
        });
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
