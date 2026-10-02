<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A unique slug made from the title, and a permanent redirect from the old
 * address when the slug of a published row changes.
 *
 * The model says which column the slug comes from (slugSource()) and where
 * a slug lives on the site (publicPath()).
 *
 * @property string $slug
 */
trait HasSlugRedirects
{
    abstract protected function slugSource(): string;

    /**
     * The site path of the row for a given slug, e.g. "/projeler/comon".
     */
    abstract public function publicPath(?string $slug = null): string;

    public static function bootHasSlugRedirects(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            if (blank($model->slug)) {
                $model->slug = $model->uniqueSlug(Str::slug((string) $model->getAttribute($model->slugSource())));
            } elseif ($model->isDirty('slug')) {
                $model->slug = $model->uniqueSlug(Str::slug($model->slug));
            }
        });

        static::updated(function (Model $model): void {
            /** @var Model&self $model */
            if (! $model->wasChanged('slug') || ! $model->wasPublishedBefore()) {
                return;
            }

            Redirect::point($model->publicPath((string) $model->getOriginal('slug')), $model->publicPath());
        });
    }

    public function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'kayit';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Only addresses visitors could have seen need a redirect.
     */
    protected function wasPublishedBefore(): bool
    {
        $publishedAt = $this->getOriginal('published_at');

        return $publishedAt !== null && now()->greaterThanOrEqualTo($publishedAt);
    }
}
