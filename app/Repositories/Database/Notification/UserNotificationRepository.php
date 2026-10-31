<?php

namespace App\Repositories\Database\Notification;

use App\Models\Notification\UserNotification;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Notification\UserNotificationRepositoryInterface;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UserNotificationRepository extends DatabaseRepository implements UserNotificationRepositoryInterface
{
    private const int INSERT_CHUNK_SIZE = 1000;

    public function __construct()
    {
        parent::__construct(UserNotification::class);
    }

    /**
     * {@inheritDoc}
     */
    public function insertDeliveries(int $notificationMessageId, array $userIds, Carbon $createdAt): void
    {
        foreach (array_chunk($userIds, self::INSERT_CHUNK_SIZE) as $chunk) {
            UserNotification::query()->insertOrIgnore(array_map(static fn(int $userId): array => [
                'user_id'                 => $userId,
                'notification_message_id' => $notificationMessageId,
                'read_at'                 => null,
                'created_at'              => $createdAt,
            ], $chunk));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countUnreadForUser(int $userId): int
    {
        return UserNotification::query()
            ->join('notification_messages', 'notification_messages.id', '=', 'user_notifications.notification_message_id')
            ->where('user_notifications.user_id', $userId)
            ->whereNull('user_notifications.read_at')
            // Keeping the count in step with what the feed shows: an expired notification is not
            // listed, and a delivery whose message was retracted mid-fan-out would otherwise leave
            // a badge the reader can never clear
            ->where(static fn(Builder|QueryBuilder $query) => $query
                ->whereNull('notification_messages.expires_at')
                ->orWhere('notification_messages.expires_at', '>', Carbon::now()))
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function existsForUserAndMessage(int $userId, int $notificationMessageId): bool
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('notification_message_id', $notificationMessageId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function markReadForUser(int $userId, int $notificationMessageId, Carbon $readAt): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('notification_message_id', $notificationMessageId)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);
    }

    /**
     * {@inheritDoc}
     */
    public function markAllReadForUser(int $userId, Carbon $readAt): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserIdsForMessage(int $notificationMessageId): array
    {
        return UserNotification::query()
            ->where('notification_message_id', $notificationMessageId)
            ->pluck('user_id')
            ->map(static fn($userId): int => (int)$userId)
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteForMessage(int $notificationMessageId): int
    {
        return UserNotification::query()
            ->where('notification_message_id', $notificationMessageId)
            ->delete();
    }
}
