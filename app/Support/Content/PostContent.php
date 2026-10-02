<?php

namespace App\Support\Content;

use App\Enums\Permission;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * Posts in the shape the site views expect.
 *
 * @phpstan-type PostData array{
 *     id: int, slug: string, title: string, excerpt: string, publishedAt: \Carbon\CarbonImmutable,
 *     readingMinutes: int, tags: list<string>, tagSlugs: list<string>, isFeatured: bool, isDraft: bool,
 *     bodyHtml: HtmlString, url: string, metaDescription: string
 * }
 */
class PostContent
{
    /**
     * Published posts, newest first.
     *
     * @return list<PostData>
     */
    public function all(): array
    {
        return array_values($this->published()->get()->map($this->toArray(...))->all());
    }

    /**
     * @return PostData|null
     */
    public function latest(): ?array
    {
        $post = $this->published()->first();

        return $post ? $this->toArray($post) : null;
    }

    /**
     * A published post, or a draft when the viewer may manage posts.
     */
    public function find(string $slug): ?Post
    {
        $post = Post::query()->with('tags')->where('slug', $slug)->first();

        if ($post === null || (! $post->isPublished() && ! Gate::allows(Permission::ManagePosts->value))) {
            return null;
        }

        return $post;
    }

    /**
     * Up to three published posts sharing the most tags, the newest first on a tie.
     *
     * @return list<PostData>
     */
    public function related(Post $post, int $limit = 3): array
    {
        $tagIds = $post->tags->pluck('id');

        if ($tagIds->isEmpty()) {
            return [];
        }

        $related = $this->published()
            ->whereKeyNot($post->id)
            ->whereHas('tags', fn (Builder $query) => $query->whereIn('tags.id', $tagIds))
            ->withCount(['tags as shared_tags_count' => fn (Builder $query) => $query->whereIn('tags.id', $tagIds)])
            ->reorder()
            ->orderByDesc('shared_tags_count')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return array_values($related->map($this->toArray(...))->all());
    }

    /**
     * The published neighbours of a post: [newer, older].
     *
     * @return array{0: PostData|null, 1: PostData|null}
     */
    public function neighbours(Post $post): array
    {
        if (! $post->isPublished()) {
            return [null, null];
        }

        $newer = $this->published()->reorder()
            ->where(fn (Builder $query) => $query->where('published_at', '>', $post->published_at)
                ->orWhere(fn (Builder $query) => $query->where('published_at', $post->published_at)->where('id', '>', $post->id)))
            ->orderBy('published_at')->orderBy('id')->first();

        $older = $this->published()
            ->where(fn (Builder $query) => $query->where('published_at', '<', $post->published_at)
                ->orWhere(fn (Builder $query) => $query->where('published_at', $post->published_at)->where('id', '<', $post->id)))
            ->first();

        return [$newer ? $this->toArray($newer) : null, $older ? $this->toArray($older) : null];
    }

    /**
     * Tags of published posts with their post counts, the most used first.
     *
     * @return list<array{name: string, slug: string, count: int}>
     */
    public function tags(): array
    {
        $tags = Tag::query()
            ->withCount(['posts' => fn (Builder $query) => $query->whereNotNull('posts.published_at')->where('posts.published_at', '<=', now())])
            ->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->get();

        return array_values($tags->map(fn (Tag $tag): array => ['name' => $tag->name, 'slug' => $tag->slug, 'count' => (int) $tag->posts_count])->all());
    }

    /**
     * @return Builder<Post>
     */
    private function published(): Builder
    {
        return Post::query()->published()->with('tags')->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * @return PostData
     */
    public function toArray(Post $post): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerptText(),
            'publishedAt' => $post->published_at ?? now(),
            'readingMinutes' => $post->reading_minutes,
            'tags' => array_values($post->tags->map(fn (Tag $tag): string => $tag->name)->all()),
            'tagSlugs' => array_values($post->tags->map(fn (Tag $tag): string => $tag->slug)->all()),
            'isFeatured' => $post->is_featured,
            'isDraft' => ! $post->isPublished(),
            'bodyHtml' => new HtmlString((string) $post->body_html),
            'url' => route('posts.show', $post->slug),
            'metaDescription' => $post->meta_description ?: $post->excerptText(),
        ];
    }
}
