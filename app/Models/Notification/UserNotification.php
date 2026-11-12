<?php

namespace App\Models\Notification;

use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One user's delivery of a {@see NotificationMessage}, carrying their own read state.
 *
 * Only written for {@see \App\Models\Notification\Enums\NotificationAudience::User} messages; a
 * site-wide message has no rows here at all.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $notification_message_id
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 *
 * @property User                $user
 * @property NotificationMessage $notificationMessage
 *
 * @mixin Eloquent
 */
class UserNotification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'notification_message_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<NotificationMessage, $this> */
    public function notificationMessage(): BelongsTo
    {
        return $this->belongsTo(NotificationMessage::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
