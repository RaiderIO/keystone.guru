<?php

namespace App\Repositories\Interfaces\Notification;

use App\Models\Notification\NotificationMessage;
use App\Models\User;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * @method NotificationMessage                  create(array<string, mixed> $attributes)
 * @method NotificationMessage|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NotificationMessage                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NotificationMessage                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                 save(NotificationMessage $model)
 * @method bool                                 update(NotificationMessage $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                 delete(NotificationMessage $model)
 * @method Collection<int, NotificationMessage> all()
 * @method bool                                 exists(array<int, string> $columns)
 */
interface NotificationMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Creates a message, or returns the existing one carrying the same dedupe key. This is what
     * makes a repeated trigger - the same mapping version imported twice, a command re-run -
     * produce one notification rather than two.
     *
     * @param array<string, mixed> $attributes
     */
    public function firstOrCreateByDedupeKey(string $dedupeKey, array $attributes): NotificationMessage;

    /**
     * Every site-wide message that has not expired, newest first, as plain arrays so the result can
     * be cached.
     *
     * @return array<int, array{id: int, created_at: string, game_version_id: int|null, expires_at: string|null}>
     */
    public function getActiveGlobalMessages(): array;

    /**
     * One page of the messages addressed to a user, newest first. Each model carries the user's own
     * read state as a `delivery_read_at` attribute.
     *
     * @return EloquentCollection<int, NotificationMessage>
     */
    public function getTargetedFeedPage(int $userId, int $limit, ?NotificationMessage $cursor): EloquentCollection;

    /**
     * One page of the site-wide messages a user can see, newest first.
     *
     * @return EloquentCollection<int, NotificationMessage>
     */
    public function getGlobalFeedPage(User $user, int $limit, ?NotificationMessage $cursor): EloquentCollection;

    /**
     * The most recently sent messages, with their delivery and read counts, for the admin overview.
     *
     * @return EloquentCollection<int, NotificationMessage>
     */
    public function getRecentWithDeliveryCounts(int $limit): EloquentCollection;
}
