<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PageViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * One view of a public page, recorded by the RecordPageView middleware for
 * the visit statistics. No IP address and no cookie: the visitor is a hash
 * that changes every day (see VisitorHash). Views older than KEEP_MONTHS are
 * pruned (`model:prune`, daily).
 *
 * @property int $id
 * @property string $path
 * @property string|null $referrer_host
 * @property string|null $utm_source
 * @property string $visitor_hash
 * @property string $browser
 * @property string $os
 * @property string $device
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['path', 'referrer_host', 'utm_source', 'visitor_hash', 'browser', 'os', 'device'])]
class PageView extends Model
{
    /** @use HasFactory<PageViewFactory> */
    use HasFactory, MassPrunable;

    public const UPDATED_AT = null;

    public const KEEP_MONTHS = 13;

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }
}
