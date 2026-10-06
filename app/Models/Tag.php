<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A tag shared by posts and notes, shown as washi tape. Its color comes
 * from the slug. A post may have several tags, a note has exactly one.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            $tag->slug = Str::slug($tag->name) ?: Str::lower((string) Str::ulid());
        });
    }

    /**
     * The washi tape color of a tag: a section color picked by the slug, so a
     * tag looks the same on every page.
     */
    public static function tapeColor(string $slug): string
    {
        $colors = ['var(--color-posts)', 'var(--color-goals)', 'var(--color-about)', 'var(--color-projects)', 'var(--color-watched)', 'var(--color-home)'];

        return $colors[crc32($slug) % count($colors)];
    }

    /**
     * The tags with these names, created when missing (in this order).
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
     * Moves every post and note of this tag to another tag and deletes this one.
     */
    public function mergeInto(Tag $target): void
    {
        if ($target->is($this)) {
            return;
        }

        DB::transaction(function () use ($target): void {
            $rows = DB::table('post_tag')->where('tag_id', $this->id)->get(['post_id', 'position']);

            DB::table('post_tag')->insertOrIgnore($rows->map(fn (object $row): array => [
                'post_id' => $row->post_id,
                'tag_id' => $target->id,
                'position' => $row->position,
            ])->all());

            $this->notes()->update(['tag_id' => $target->id]);

            $this->delete();
        });
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withPivot('position');
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Notes cannot lose their only tag, so a tag that holds notes can only be merged.
     */
    public function isDeletable(): bool
    {
        return ! $this->notes()->exists();
    }
}
