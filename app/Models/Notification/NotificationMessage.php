<?php

namespace App\Models\Notification;

use App\Models\GameVersion\GameVersion;
use App\Models\Notification\Enums\NotificationAudience;
use App\Models\Notification\Enums\NotificationType;
use App\Models\User;
use Database\Factories\Notification\NotificationMessageFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The content of a notification, stored once regardless of how many users receive it.
 *
 * Nothing here is rendered text: a translatable type stores ids in `params` and is rendered in the
 * reader's own locale when the feed is read. Only a plain-text type ({@see NotificationType::isPlainText()})
 * fills `body`.
 *
 * @property int                       $id
 * @property NotificationType          $type
 * @property NotificationAudience      $audience
 * @property string|null               $body
 * @property array<string, mixed>|null $params
 * @property string|null               $url
 * @property int|null                  $game_version_id
 * @property bool                      $bypasses_preferences
 * @property int|null                  $created_by
 * @property string|null               $dedupe_key
 * @property Carbon|null               $expires_at
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property User|null                         $createdBy
 * @property GameVersion|null                  $gameVersion
 * @property Collection<int, UserNotification> $userNotifications
 *
 * @mixin Eloquent
 */
class NotificationMessage extends Model
{
    /** @use HasFactory<NotificationMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'audience',
        'body',
        'params',
        'url',
        'game_version_id',
        'bypasses_preferences',
        'created_by',
        'dedupe_key',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type'                 => NotificationType::class,
            'audience'             => NotificationAudience::class,
            'params'               => 'array',
            'bypasses_preferences' => 'boolean',
            'expires_at'           => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<GameVersion, $this> */
    public function gameVersion(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class);
    }

    /** @return HasMany<UserNotification, $this> */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function isGlobal(): bool
    {
        return $this->audience === NotificationAudience::Global;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
