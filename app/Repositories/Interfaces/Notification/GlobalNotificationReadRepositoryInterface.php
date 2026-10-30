<?php

namespace App\Repositories\Interfaces\Notification;

use App\Models\Notification\GlobalNotificationRead;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method GlobalNotificationRead                  create(array<string, mixed> $attributes)
 * @method GlobalNotificationRead|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method GlobalNotificationRead                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method GlobalNotificationRead                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(GlobalNotificationRead $model)
 * @method bool                                    update(GlobalNotificationRead $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(GlobalNotificationRead $model)
 * @method Collection<int, GlobalNotificationRead> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface GlobalNotificationReadRepositoryInterface extends BaseRepositoryInterface
{
    public function markRead(int $userId, int $notificationMessageId, Carbon $readAt): void;

    /**
     * Which of the given site-wide messages this user has already read.
     *
     * @param  array<int, int> $notificationMessageIds
     * @return array<int, int>
     */
    public function getReadMessageIdsForUser(int $userId, array $notificationMessageIds): array;

    public function deleteForMessage(int $notificationMessageId): int;
}
