<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * A message from the contact form. It is kept for Kadir's inbox in the admin
 * panel (and also e-mailed); no IP address or account is stored with it.
 * Messages older than a year are pruned (`model:prune`, daily).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $body
 * @property CarbonImmutable|null $read_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'email', 'body'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory, MassPrunable;

    public const KEEP_MONTHS = 12;

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public static function unreadCount(): int
    {
        return static::query()->whereNull('read_at')->count();
    }

    protected function casts(): array
    {
        return ['read_at' => 'immutable_datetime'];
    }
}
