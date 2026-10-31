<?php

namespace App\Service\Notification;

use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use App\Models\User;
use App\Service\Notification\Dtos\RenderedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Sending and reading notifications.
 *
 * Sending is meant to be a one-liner from anywhere:
 * `$notificationService->notifyUser($user, NotificationType::AdminCustom);`
 */
interface NotificationServiceInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function notifyUser(
        User|int         $user,
        NotificationType $type,
        array            $params = [],
        ?string          $url = null,
        ?string          $dedupeKey = null,
    ): NotificationMessage;

    /**
     * @param array<int, int>      $userIds
     * @param array<string, mixed> $params
     */
    public function notifyUsers(
        array            $userIds,
        NotificationType $type,
        array            $params = [],
        ?string          $url = null,
        ?string          $dedupeKey = null,
    ): NotificationMessage;

    /**
     * @param array<string, mixed> $params
     */
    public function notifyTarget(
        NotificationTarget $target,
        NotificationType   $type,
        array              $params = [],
        ?string            $url = null,
        ?string            $dedupeKey = null,
    ): NotificationMessage;

    /**
     * Notifies every registered user with a single stored row, optionally limited to the users who
     * play one game version. No delivery rows are written, so the cost does not grow with the
     * number of accounts.
     *
     * @param array<string, mixed> $params
     */
    public function notifyEveryone(
        NotificationType $type,
        array            $params = [],
        ?int             $gameVersionId = null,
        ?string          $url = null,
        ?string          $dedupeKey = null,
    ): NotificationMessage;

    /**
     * Sends an admin's free-text announcement, shown verbatim in the language it was typed in.
     *
     * Internal-only by construction: {@see NotificationType::supportedChannels()} does not list the
     * mail channel for this type, so this can never turn into mail to the whole user base.
     */
    public function sendCustom(
        string             $body,
        NotificationTarget $target,
        int                $createdBy,
        ?string            $url = null,
        ?Carbon            $expiresAt = null,
    ): NotificationMessage;

    /**
     * Delivers an existing message to a known set of users over every channel its type allows.
     * Called directly by the fan-out job, chunk by chunk.
     *
     * @param array<int, int> $userIds
     */
    public function deliverToUserIds(NotificationMessage $notificationMessage, array $userIds): void;

    /**
     * The number of notifications a user has not read yet, counting both the ones addressed to them
     * and the site-wide ones they can still see. Safe to call on every page render.
     */
    public function getUnreadCount(User $user): int;

    /**
     * One page of a user's notifications, newest first, rendered in their locale.
     *
     * @return Collection<int, RenderedNotification>
     */
    public function getFeed(User $user, int $limit = 20, ?int $beforeMessageId = null): Collection;

    /**
     * Marks one notification as read. Returns false when the user cannot see that notification at
     * all, which is how an attempt to touch somebody else's notification is rejected.
     */
    public function markRead(User $user, int $notificationMessageId): bool;

    public function markAllRead(User $user): void;

    /**
     * Withdraws a notification: deletes it along with every delivery and read record of it.
     */
    public function retract(NotificationMessage $notificationMessage): void;
}
