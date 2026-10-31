<?php

namespace App\Repositories\Database\Notification;

use App\Models\Notification\GlobalNotificationRead;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Notification\GlobalNotificationReadRepositoryInterface;
use Illuminate\Support\Carbon;

class GlobalNotificationReadRepository extends DatabaseRepository implements GlobalNotificationReadRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(GlobalNotificationRead::class);
    }

    /**
     * {@inheritDoc}
     */
    public function markRead(int $userId, int $notificationMessageId, Carbon $readAt): void
    {
        GlobalNotificationRead::query()->updateOrCreate([
            'user_id'                 => $userId,
            'notification_message_id' => $notificationMessageId,
        ], [
            'read_at' => $readAt,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getReadMessageIdsForUser(int $userId, array $notificationMessageIds): array
    {
        if ($notificationMessageIds === []) {
            return [];
        }

        return GlobalNotificationRead::query()
            ->where('user_id', $userId)
            ->whereIn('notification_message_id', $notificationMessageIds)
            ->pluck('notification_message_id')
            ->map(static fn($id): int => (int)$id)
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteForMessage(int $notificationMessageId): int
    {
        return GlobalNotificationRead::query()
            ->where('notification_message_id', $notificationMessageId)
            ->delete();
    }
}
