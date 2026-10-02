<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\RendersMarkdown;
use App\Models\Concerns\Sortable;
use App\Support\Images\ImageStore;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A project with its case study (Markdown), screenshots and devlog.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $tagline
 * @property ProjectStatus $status
 * @property int $started_year
 * @property string|null $cover_path
 * @property string|null $demo_url
 * @property string|null $repo_url
 * @property string|null $body
 * @property string|null $body_html
 * @property string|null $meta_description
 * @property bool $is_featured
 * @property int $sort_order
 * @property int|null $goal_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'slug', 'tagline', 'status', 'started_year', 'demo_url', 'repo_url', 'body', 'meta_description', 'is_featured', 'published_at', 'sort_order', 'goal_id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasPublication, HasSlugRedirects, RendersMarkdown, Sortable;

    protected static function booted(): void
    {
        // Only one project is featured on the home page.
        static::saved(function (Project $project): void {
            if ($project->is_featured && ($project->wasChanged('is_featured') || $project->wasRecentlyCreated)) {
                static::query()->whereKeyNot($project->getKey())->where('is_featured', true)->update(['is_featured' => false]);
            }
        });

        static::deleting(function (Project $project): void {
            $project->images->each->delete();
            $project->devlog()->delete();
            app(ImageStore::class)->delete($project->cover_path);
        });
    }

    /**
     * @return BelongsToMany<Technology, $this>
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class)->withPivot('position')->orderByPivot('position');
    }

    /**
     * @return HasMany<ProjectImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return MorphMany<DevlogEntry, $this>
     */
    public function devlog(): MorphMany
    {
        return $this->morphMany(DevlogEntry::class, 'loggable')->orderByDesc('date')->orderByDesc('id');
    }

    /**
     * @param  list<string>  $names
     */
    public function syncTechnologyNames(array $names): void
    {
        $ids = Technology::idsForNames($names);

        $this->technologies()->sync(collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all());
    }

    public function coverUrl(int $width = 960): ?string
    {
        return ImageStore::url($this->cover_path, $width);
    }

    public function publicPath(?string $slug = null): string
    {
        return '/projeler/'.($slug ?? $this->slug);
    }

    protected function slugSource(): string
    {
        return 'name';
    }

    protected function markdownColumns(): array
    {
        return ['body' => 'body_html'];
    }

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'is_featured' => 'boolean',
            'started_year' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
