<?php

namespace App\Models;

use App\Enums\ToolboxGroup;
use App\Models\Concerns\HasNameSlug;
use App\Models\Concerns\Sortable;
use Carbon\CarbonImmutable;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    use HasFactory, HasNameSlug, Sortable;

    /**
     * Puts the technology at the end of a toolbox group, or takes it out of
     * the toolbox (null). It stays on its projects either way.
     */
    public function placeInToolbox(?ToolboxGroup $group): void
    {
        $this->toolbox_group = $group;
        $this->toolbox_order = $group === null ? 0 : $this->nextSortOrder();
        $this->save();
    }

    /**
     * Moves the technology to a zero-based position within its toolbox group
     * and renumbers the group.
     */
    public function moveInToolbox(int $position): void
    {
        if ($this->toolbox_group !== null) {
            $this->moveTo($position);
        }
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    protected function sortColumn(): string
    {
        return 'toolbox_order';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->where('toolbox_group', $this->toolbox_group);
    }

    protected function casts(): array
    {
        return [
            'toolbox_group' => ToolboxGroup::class,
        ];
    }
}
