<?php

namespace App\Models;

use App\Enums\NotificationFrequency;
use App\Enums\Permission;
use App\Enums\SystemRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $blocked_at
 * @property NotificationFrequency $notification_frequency
 * @property bool $notify_new_posts
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /**
     * The database defaults, so a fresh model has them before it is reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'notification_frequency' => 'daily',
        'notify_new_posts' => false,
    ];

    /**
     * Session key holding the id of the user who last verified a passkey.
     */
    public const PASSKEY_SESSION_KEY = 'auth.passkey_user_id';

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'blocked_at' => 'datetime',
            'notification_frequency' => NotificationFrequency::class,
            'notify_new_posts' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<Follow, $this>
     */
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    /**
     * @return HasMany<NotificationItem, $this>
     */
    public function notificationItems(): HasMany
    {
        return $this->hasMany(NotificationItem::class);
    }

    /**
     * Whether we send this user notification e-mail at all.
     */
    public function receivesNotifications(): bool
    {
        return $this->hasVerifiedEmail() && ! $this->isBlocked() && $this->notification_frequency !== NotificationFrequency::Never;
    }

    /**
     * Whether the user may write a comment right now.
     */
    public function canComment(): bool
    {
        return $this->hasVerifiedEmail() && ! $this->isBlocked() && $this->can(Permission::CreateComments->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(SystemRole::Admin->value);
    }

    /**
     * Blocked users can still sign in, but cannot comment and get no e-mail.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * Whether the current login is strong enough for the admin panel: either
     * every password login is guarded by two-factor authentication, or this
     * session was started (or confirmed) with a passkey.
     */
    public function hasStrongLogin(?Session $session): bool
    {
        return $this->hasEnabledTwoFactorAuthentication()
            || ($session?->get(self::PASSKEY_SESSION_KEY) === $this->getKey());
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
