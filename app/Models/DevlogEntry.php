<?php

namespace App\Models;

use App\Models\Concerns\RendersMarkdown;
use Carbon\CarbonImmutable;
use Database\Factories\DevlogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A dated note in a logbook: a project's devlog or the updates of a
 * long-term goal (same component on the site).
 *
 * @property int $id
 * @property string $loggable_type
 * @property int $loggable_id
 * @property CarbonImmutable $date
 * @property string $body
 * @property string|null $body_html
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['date', 'body'])]
class DevlogEntry extends Model
{
    /** @use HasFactory<DevlogEntryFactory> */
    use HasFactory, RendersMarkdown;

    /**
     * @return MorphTo<Model, $this>
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function markdownColumns(): array
    {
        return ['body' => 'body_html'];
    }

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }
}
