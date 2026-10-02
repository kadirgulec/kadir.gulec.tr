<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSlugRedirects;
use App\Models\Concerns\RendersMarkdown;
use App\Support\Markdown\Markdown;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A blog post written in Markdown. Reading time and, when left empty, the
 * excerpt are worked out on save.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $body_html
 * @property int $reading_minutes
 * @property string|null $meta_description
 * @property bool $is_featured
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'slug', 'excerpt', 'body', 'meta_description', 'is_featured', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasPublication, HasSlugRedirects, RendersMarkdown;

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->isDirty('body') || $post->wasRecentlyCreated || ! $post->exists) {
                $post->reading_minutes = app(Markdown::class)->readingMinutes((string) $post->body);
            }
        });
    }

    /**
     * The excerpt Kadir wrote, or one made from the first paragraph.
     */
    public function excerptText(): string
    {
        return filled($this->excerpt) ? (string) $this->excerpt : app(Markdown::class)->excerpt((string) $this->body);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withPivot('position')->orderByPivot('position');
    }

    /**
     * @param  list<string>  $names
     */
    public function syncTagNames(array $names): void
    {
        $ids = Tag::idsForNames($names);

        $this->tags()->sync(collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all());
    }

    public function publicPath(?string $slug = null): string
    {
        return '/yazilar/'.($slug ?? $this->slug);
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    protected function markdownColumns(): array
    {
        return ['body' => 'body_html'];
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'reading_minutes' => 'integer',
        ];
    }
}
