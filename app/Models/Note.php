<?php

namespace App\Models;

use App\Models\Concerns\HasPublication;
use App\Models\Concerns\RendersMarkdown;
use Carbon\CarbonImmutable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A small thing Kadir learned: two or three sentences of Markdown on a post-it,
 * without a title, held by exactly one tag. Its number is its address.
 *
 * @property int $id
 * @property int $tag_id
 * @property string $body
 * @property string|null $body_html
 * @property CarbonImmutable|null $announced_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tag $tag
 */
#[Fillable(['body', 'published_at'])]
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
     * Holds the note by the tag with this name, created when missing.
     */
    public function useTagNamed(string $name): void
    {
        $this->tag()->associate(Tag::query()->firstOrCreate(['name' => trim($name)]));
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
