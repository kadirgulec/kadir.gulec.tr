<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use App\Support\Images\ImageStore;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A screenshot in a project's gallery. Deleting the row deletes its files.
 *
 * @property int $id
 * @property int $project_id
 * @property string $path
 * @property string|null $caption
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['path', 'caption', 'sort_order'])]
class ProjectImage extends Model
{
    /** @use HasFactory<ProjectImageFactory> */
    use HasFactory, Sortable;

    protected static function booted(): void
    {
        static::deleted(fn (ProjectImage $image) => app(ImageStore::class)->delete($image->path));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function sortSiblings(Builder $query): Builder
    {
        return $query->where('project_id', $this->project_id);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function url(int $width = 960): ?string
    {
        return ImageStore::url($this->path, $width);
    }
}
