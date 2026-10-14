<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\RendersMarkdown;
use App\Support\Markdown\Markdown;
use Carbon\CarbonImmutable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A small thing Kadir learned: two or three sentences of Markdown on a post-it,
 * without a title, held by exactly one tag. Its number is its address. It may
 * name the book or film/series it came from (its source).
 *
 * @property int $id
 * @property int $tag_id
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string $body
 * @property string|null $body_html
 * @property CarbonImmutable|null $announced_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tag $tag
 */
#[Fillable(['body', 'published_at', 'source_type', 'source_id'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, HasPublication, RendersMarkdown;

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * The book or film/series the note came from, if any.
     *
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Holds the note by the tag with this name, created when missing.
     */
    public function useTagNamed(string $name): void
    {
        $this->tag()->associate(Tag::query()->firstOrCreate(['name' => trim($name)]));
    }

    /**
     * The note as plain text, without its sidenotes (taken from the stored HTML).
     */
    public function text(): string
    {
        return app(Markdown::class)->plainText((string) $this->body_html);
    }

    public function publicPath(): string
    {
        return '/ogrendiklerim/'.$this->id;
    }

    protected function markdownColumns(): array
    {
        return ['body' => 'body_html'];
    }
}
