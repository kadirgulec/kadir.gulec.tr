<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One thing to tell one member, waiting for the next e-mail. The title is
 * already written for that member (censored goals stay censored). The key
 * is unique per member, so saving something twice never sends it twice.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $key
 * @property string $title
 * @property string|null $body
 * @property string $url
 * @property bool $digest_only
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'key', 'title', 'body', 'url', 'digest_only', 'sent_at'])]
class NotificationItem extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return ['digest_only' => 'boolean', 'sent_at' => 'immutable_datetime'];
    }
}
