<?php

namespace App\Models\Notification;

use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A user having read one site-wide notification.
 *
 * Written lazily, only when a user reads such a message, so a site-wide announcement stays a single
 * row until somebody interacts with it. "Mark all as read" does not write these at all - it moves
 * the high-water mark on the user instead.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $notification_message_id
 * @property Carbon|null $read_at
 *
 * @property User                $user
 * @property NotificationMessage $notificationMessage
 *
 * @mixin Eloquent
 */
class GlobalNotificationRead extends Model
{
    public $timestamps = false;

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
}
