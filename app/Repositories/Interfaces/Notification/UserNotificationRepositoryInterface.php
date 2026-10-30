<?php

namespace App\Repositories\Interfaces\Notification;

use App\Models\Notification\UserNotification;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method UserNotification                  create(array<string, mixed> $attributes)
 * @method UserNotification|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method UserNotification                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method UserNotification                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(UserNotification $model)
 * @method bool                              update(UserNotification $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(UserNotification $model)
 * @method Collection<int, UserNotification> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface UserNotificationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Writes one delivery per user, ignoring any that already exist.
     *
     * The unique index on (message, user) plus insertOrIgnore is what makes a retried fan-out job
     * or a repeated trigger a no-op instead of a second notification.
     *
     * @param array<int, int> $userIds
     */
    public function insertDeliveries(int $notificationMessageId, array $userIds, Carbon $createdAt): void;

    public function countUnreadForUser(int $userId): int;

    public function existsForUserAndMessage(int $userId, int $notificationMessageId): bool;

    /**
     * Marks one message read for a user. Returns the number of deliveries actually updated, which
     * is zero when it was already read.
     */
    public function markReadForUser(int $userId, int $notificationMessageId, Carbon $readAt): int;

    public function markAllReadForUser(int $userId, Carbon $readAt): int;

    /**
     * The ids of every user holding a delivery of this message.
     *
     * @return array<int, int>
     */
    public function getUserIdsForMessage(int $notificationMessageId): array;

    public function deleteForMessage(int $notificationMessageId): int;
}
