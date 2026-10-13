<?php

namespace App\Support\Content;

use App\Enums\Permission;
use App\Models\Book;
use App\Models\Note;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Watchable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * Notes in the shape the site views expect.
 *
 * @phpstan-type NoteData array{
 *     id: int, bodyHtml: HtmlString, text: string, tagName: string, tagSlug: string,
 *     color: string, tilt: float, publishedAt: \Carbon\CarbonImmutable, isDraft: bool, url: string,
 *     source: array{title: string, url: ?string}|null
 * }
 */
class NoteContent
{
    /** The pastel papers of x-site.post-it, picked by the note's number. */
    public const COLORS = ['yellow', 'pink', 'blue', 'green', 'orange'];

    public const PER_PAGE = 30;

    /**
     * One page of the board, newest first, optionally only one tag's notes.
     *
     * @return LengthAwarePaginator<int, NoteData>
     */
    public function board(?string $tagSlug = null): LengthAwarePaginator
    {
        return $this->published()
            ->when($tagSlug, fn (Builder $query) => $query->whereRelation('tag', 'slug', $tagSlug))
            ->paginate(self::PER_PAGE, pageName: 'sayfa')
            ->withQueryString()
            ->through($this->toArray(...));
    }

    /**
     * Tags holding published notes, with their note counts, the most used first.
     *
     * @return list<array{name: string, slug: string, count: int}>
     */
    public function tags(): array
    {
        $published = fn ($query) => $query->published();

        $tags = Tag::query()
            ->whereHas('notes', $published)
            ->withCount(['notes' => $published])
            ->orderByDesc('notes_count')
            ->orderBy('name')
            ->get();

        return array_values($tags->map(fn (Tag $tag): array => ['name' => $tag->name, 'slug' => $tag->slug, 'count' => (int) $tag->notes_count])->all());
    }

    /**
     * @return NoteData|null
     */
    public function latest(): ?array
    {
        $note = $this->published()->first();

        return $note ? $this->toArray($note) : null;
    }

    /**
     * A published note, or a draft when the viewer may manage notes.
     */
    public function find(int $id): ?Note
    {
        $note = Note::query()->with(['tag', 'source'])->find($id);

        if ($note === null || (! $note->isPublished() && ! Gate::allows(Permission::ManageNotes->value))) {
            return null;
        }

        return $note;
    }

    /**
     * The published neighbours of a note: [newer, older].
     *
     * @return array{0: NoteData|null, 1: NoteData|null}
     */
    public function neighbours(Note $note): array
    {
        if (! $note->isPublished()) {
            return [null, null];
        }

        $newer = $this->published()->newerThan($note)->first();
        $older = $this->published()->olderThan($note)->first();

        return [$newer ? $this->toArray($newer) : null, $older ? $this->toArray($older) : null];
    }

    /**
     * Other published notes held by the same tag, newest first.
     *
     * @return list<NoteData>
     */
    public function sameTag(Note $note, int $limit = 3): array
    {
        $notes = $this->published()->whereKeyNot($note->id)->where('tag_id', $note->tag_id)->limit($limit)->get();

        return array_values($notes->map($this->toArray(...))->all());
    }

    /**
     * Published notes held by any of the post's tags, newest first.
     *
     * @return list<NoteData>
     */
    public function forPost(Post $post, int $limit = 3): array
    {
        $tagIds = $post->tags->pluck('id');

        if ($tagIds->isEmpty()) {
            return [];
        }

        $notes = $this->published()->whereIn('tag_id', $tagIds)->limit($limit)->get();

        return array_values($notes->map($this->toArray(...))->all());
    }

    /**
     * Published notes that name this book or film/series as their source, newest first.
     *
     * @return list<NoteData>
     */
    public function forSource(string $type, int $id): array
    {
        $notes = $this->published()->where('source_type', $type)->where('source_id', $id)->get();

        return array_values($notes->map($this->toArray(...))->all());
    }

    /**
     * @return Builder<Note>
     */
    private function published(): Builder
    {
        return Note::query()->published()->with(['tag', 'source'])->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * @return NoteData
     */
    public function toArray(Note $note): array
    {
        return [
            'id' => $note->id,
            'bodyHtml' => new HtmlString((string) $note->body_html),
            'text' => $note->text(),
            'tagName' => $note->tag->name,
            'tagSlug' => $note->tag->slug,
            'color' => self::COLORS[$note->id % count(self::COLORS)],
            // Between -2 and 2 degrees in half steps, always the same for a note
            'tilt' => (($note->id * 7) % 9 - 4) / 2,
            'publishedAt' => $note->published_at ?? now()->toImmutable(),
            'isDraft' => ! $note->isPublished(),
            'url' => route('notes.show', $note->id),
            'source' => $this->source($note),
        ];
    }

    /**
     * The note's book or film/series; a draft one is named but not linked.
     *
     * @return array{title: string, url: ?string}|null
     */
    private function source(Note $note): ?array
    {
        $source = $note->source;

        if (! $source instanceof Book && ! $source instanceof Watchable) {
            return null;
        }

        return ['title' => $source->title, 'url' => $source->isPublished() ? url($source->publicPath()) : null];
    }
}
