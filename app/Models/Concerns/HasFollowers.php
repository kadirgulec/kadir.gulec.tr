<?php

namespace App\Models\Concerns;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Members can follow this model and hear about it by e-mail.
 */
trait HasFollowers
{
    public static function bootHasFollowers(): void
    {
        // No return value: a non-null answer would stop the other "deleting" listeners.
        static::deleting(function ($model): void {
            $model->follows()->delete();
        });
    }

    /**
     * @return MorphMany<Follow, $this>
     */
    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function isFollowedBy(?User $user): bool
    {
        return $user !== null && $this->follows()->where('user_id', $user->id)->exists();
    }
}
