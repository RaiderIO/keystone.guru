<?php

namespace App\Service\Notification;

use App\Models\User;
use App\Service\Notification\Enums\NotificationTargetKind;
use Illuminate\Database\Eloquent\Builder;

/**
 * A description of who should receive a notification, as a query that can be rebuilt rather than a
 * resolved list of users.
 *
 * The fan-out job carries one of these, so a send to a hundred thousand users stays a few bytes on
 * the queue and the job can re-run from scratch after a failure.
 */
readonly class NotificationTarget
{
    /**
     * @param array<int, int> $userIds
     */
    private function __construct(
        public NotificationTargetKind $kind,
        public array                  $userIds = [],
        public ?string                $role = null,
    ) {
    }

    /**
     * @param array<int, int> $userIds
     */
    public static function userIds(array $userIds): self
    {
        return new self(NotificationTargetKind::UserIds, array_values(array_unique(array_map('intval', $userIds))));
    }

    public static function user(User|int $user): self
    {
        return self::userIds([$user instanceof User ? $user->id : $user]);
    }

    public static function role(string $role): self
    {
        return new self(NotificationTargetKind::Role, role: $role);
    }

    public static function everyone(): self
    {
        return new self(NotificationTargetKind::Everyone);
    }

    /**
     * A fresh query selecting only the ids of the users this target covers.
     *
     * @return Builder<User>
     */
    public function newUserIdQuery(): Builder
    {
        $query = User::query()->select('users.id');

        return match ($this->kind) {
            NotificationTargetKind::UserIds  => $query->whereIn('users.id', $this->userIds === [] ? [0] : $this->userIds),
            NotificationTargetKind::Role     => $query->whereHas('roles', fn($roleQuery) => $roleQuery->where('name', $this->role)),
            NotificationTargetKind::Everyone => $query,
        };
    }
}
