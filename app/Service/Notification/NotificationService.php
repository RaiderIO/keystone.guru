<?php

namespace App\Service\Notification;

use App\Jobs\Notification\FanOutNotification;
use App\Models\Notification\Enums\NotificationAudience;
use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use App\Models\User;
use App\Repositories\Interfaces\Notification\GlobalNotificationReadRepositoryInterface;
use App\Repositories\Interfaces\Notification\NotificationMessageRepositoryInterface;
use App\Repositories\Interfaces\Notification\UserNotificationRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Notification\Channel\NotificationChannelInterface;
use App\Service\Notification\Dtos\RenderedNotification;
use App\Service\Notification\Enums\NotificationTargetKind;
use App\Service\Notification\Logging\NotificationServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NotificationService implements NotificationServiceInterface
{
    private const string ACTIVE_GLOBALS_CACHE_KEY = 'notifications:globals:active';

    private const string UNREAD_COUNT_CACHE_KEY_FORMAT = 'notifications:unread:%d';

    private const string CACHE_TTL = '5 minutes';

    /**
     * Above this many recipients the fan-out moves to the queue. Below it the handful of rows is
     * written in the request, so the notification is there the moment the sender is redirected.
     */
    private const int INLINE_DELIVERY_THRESHOLD = 200;

    /**
     * @param array<int, NotificationChannelInterface> $channels
     */
    public function __construct(
        private readonly NotificationMessageRepositoryInterface    $notificationMessageRepository,
        private readonly UserNotificationRepositoryInterface       $userNotificationRepository,
        private readonly GlobalNotificationReadRepositoryInterface $globalNotificationReadRepository,
        private readonly NotificationRenderServiceInterface        $notificationRenderService,
        private readonly CacheServiceInterface                     $cacheService,
        private readonly NotificationServiceLoggingInterface       $log,
        private readonly array                                     $channels,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function notifyUser(
        User|int         $user,
        NotificationType $type,
        array            $params = [],
        ?string          $url = null,
        ?string          $dedupeKey = null,
    ): NotificationMessage {
        return $this->notifyTarget(NotificationTarget::user($user), $type, $params, $url, $dedupeKey);
    }

    /**
     * {@inheritDoc}
     */
    public function notifyUsers(
        array            $userIds,
        NotificationType $type,
        array            $params = [],
        ?string          $url = null,
        ?string          $dedupeKey = null,
    ): NotificationMessage {
        return $this->notifyTarget(NotificationTarget::userIds($userIds), $type, $params, $url, $dedupeKey);
    }

    /**
     * {@inheritDoc}
     */
    public function notifyTarget(
        NotificationTarget $target,
        NotificationType   $type,
        array              $params = [],
        ?string            $url = null,
        ?string            $dedupeKey = null,
    ): NotificationMessage {
        if ($target->kind === NotificationTargetKind::Everyone) {
            return $this->notifyEveryone($type, $params, null, $url, $dedupeKey);
        }

        $notificationMessage = $this->createMessage([
            'type'     => $type,
            'audience' => NotificationAudience::User,
            'params'   => $params === [] ? null : $params,
            'url'      => $url,
        ], $dedupeKey);

        $this->deliver($notificationMessage, $target);

        return $notificationMessage;
    }

    /**
     * {@inheritDoc}
     */
    public function notifyEveryone(
        NotificationType $type,
        array            $params = [],
        ?int             $gameVersionId = null,
        ?string          $url = null,
        ?string          $dedupeKey = null,
    ): NotificationMessage {
        $notificationMessage = $this->createMessage([
            'type'            => $type,
            'audience'        => NotificationAudience::Global,
            'params'          => $params === [] ? null : $params,
            'url'             => $url,
            'game_version_id' => $gameVersionId,
        ], $dedupeKey);

        $this->refreshActiveGlobalsCache();

        return $notificationMessage;
    }

    /**
     * {@inheritDoc}
     */
    public function sendCustom(
        string             $body,
        NotificationTarget $target,
        int                $createdBy,
        ?string            $url = null,
        ?Carbon            $expiresAt = null,
    ): NotificationMessage {
        $isGlobal = $target->kind === NotificationTargetKind::Everyone;

        $notificationMessage = $this->createMessage([
            'type'       => NotificationType::AdminCustom,
            'audience'   => $isGlobal ? NotificationAudience::Global : NotificationAudience::User,
            'body'       => $body,
            'url'        => $url,
            'created_by' => $createdBy,
            'expires_at' => $expiresAt,
        ], null);

        if ($isGlobal) {
            $this->refreshActiveGlobalsCache();
        } else {
            $this->deliver($notificationMessage, $target);
        }

        return $notificationMessage;
    }

    /**
     * {@inheritDoc}
     */
    public function deliverToUserIds(NotificationMessage $notificationMessage, array $userIds): void
    {
        $eligibleUserIds = $this->filterEligibleUserIds($notificationMessage, $userIds);

        if ($eligibleUserIds === []) {
            return;
        }

        foreach ($this->channelsFor($notificationMessage) as $channel) {
            $channel->deliver($notificationMessage, $eligibleUserIds);
        }

        $this->forgetUnreadCounts($eligibleUserIds);
    }

    /**
     * {@inheritDoc}
     */
    public function getUnreadCount(User $user): int
    {
        if (!$user->hasNotificationsEnabled()) {
            return 0;
        }

        $targeted = $this->cacheService->remember(
            sprintf(self::UNREAD_COUNT_CACHE_KEY_FORMAT, $user->id),
            fn(): int => $this->userNotificationRepository->countUnreadForUser($user->id),
            self::CACHE_TTL,
        );

        // This runs on every page render, so a cache handing back something other than the int we
        // wrote must fall back to a direct read rather than report a wrong count
        if (!is_int($targeted)) {
            $targeted = $this->userNotificationRepository->countUnreadForUser($user->id);
        }

        return $targeted + count($this->getUnreadGlobalMessageIds($user));
    }

    /**
     * {@inheritDoc}
     */
    public function getFeed(User $user, int $limit = 20, ?int $beforeMessageId = null): Collection
    {
        if (!$user->hasNotificationsEnabled()) {
            return collect();
        }

        $cursor = $beforeMessageId === null ? null : $this->notificationMessageRepository->find($beforeMessageId);

        // Targeted and site-wide notifications live in different shapes, so a page is the newest
        // $limit of each, merged and cut back down to $limit
        $messages = $this->notificationMessageRepository->getTargetedFeedPage($user->id, $limit, $cursor)
            ->concat($this->notificationMessageRepository->getGlobalFeedPage($user, $limit, $cursor))
            ->sortByDesc(static fn(NotificationMessage $message): array => [$message->created_at->getTimestamp(), $message->id])
            ->take($limit)
            ->values();

        $readGlobalIds = $this->globalNotificationReadRepository->getReadMessageIdsForUser(
            $user->id,
            $messages->filter(static fn(NotificationMessage $message): bool => $message->isGlobal())
                ->map(static fn(NotificationMessage $message): int => $message->id)
                ->all(),
        );

        $locale = $user->locale ?? (string)config('app.locale');

        return $messages->map(fn(NotificationMessage $message): RenderedNotification => $this->notificationRenderService->render(
            $message,
            $locale,
            $this->isRead($user, $message, $readGlobalIds),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function markRead(User $user, int $notificationMessageId): bool
    {
        $notificationMessage = $this->notificationMessageRepository->find($notificationMessageId);

        if ($notificationMessage === null) {
            return false;
        }

        if ($notificationMessage->isGlobal()) {
            if (!$this->isGlobalVisibleTo($user, $notificationMessage)) {
                $this->log->markReadNotVisible($notificationMessageId, $user->id);

                return false;
            }

            $this->globalNotificationReadRepository->markRead($user->id, $notificationMessage->id, Carbon::now());
        } else {
            // No delivery row means this notification was never addressed to this user, so there is
            // nothing of theirs to mark - that is how somebody else's notification gets rejected
            if (!$this->userNotificationRepository->existsForUserAndMessage($user->id, $notificationMessage->id)) {
                $this->log->markReadNotVisible($notificationMessageId, $user->id);

                return false;
            }

            $this->userNotificationRepository->markReadForUser($user->id, $notificationMessage->id, Carbon::now());
        }

        $this->forgetUnreadCounts([$user->id]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function markAllRead(User $user): void
    {
        $now = Carbon::now();

        $this->userNotificationRepository->markAllReadForUser($user->id, $now);

        // The high-water mark covers every site-wide message up to now in one write, so marking all
        // as read never scales with the number of announcements
        User::query()->whereKey($user->id)->update(['global_notifications_read_at' => $now]);
        $user->global_notifications_read_at = $now;

        $this->forgetUnreadCounts([$user->id]);
    }

    /**
     * {@inheritDoc}
     */
    public function retract(NotificationMessage $notificationMessage): void
    {
        $notificationMessageId = $notificationMessage->id;
        $wasGlobal             = $notificationMessage->isGlobal();

        $userIds = $this->userNotificationRepository->getUserIdsForMessage($notificationMessageId);

        $this->userNotificationRepository->deleteForMessage($notificationMessageId);
        $this->globalNotificationReadRepository->deleteForMessage($notificationMessageId);
        $this->notificationMessageRepository->delete($notificationMessage);

        if ($wasGlobal) {
            $this->refreshActiveGlobalsCache();
        }

        $this->forgetUnreadCounts($userIds);

        $this->log->retract($notificationMessageId, count($userIds));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createMessage(array $attributes, ?string $dedupeKey): NotificationMessage
    {
        if ($dedupeKey === null) {
            return $this->notificationMessageRepository->create($attributes);
        }

        $notificationMessage = $this->notificationMessageRepository->firstOrCreateByDedupeKey(
            $dedupeKey,
            [...$attributes, 'dedupe_key' => $dedupeKey],
        );

        if (!$notificationMessage->wasRecentlyCreated) {
            $this->log->createMessageDeduplicated($dedupeKey, $notificationMessage->id);
        }

        return $notificationMessage;
    }

    private function deliver(NotificationMessage $notificationMessage, NotificationTarget $target): void
    {
        $userCount = $target->newUserIdQuery()->count();

        if ($userCount > self::INLINE_DELIVERY_THRESHOLD) {
            FanOutNotification::dispatch($notificationMessage->id, $target);

            $this->log->deliverDispatchedToQueue($notificationMessage->id, $userCount);

            return;
        }

        $this->log->deliverInline($notificationMessage->id, $userCount);

        $this->deliverToUserIds(
            $notificationMessage,
            $target->newUserIdQuery()->pluck('users.id')->map(static fn($id): int => (int)$id)->all(),
        );
    }

    /**
     * Drops the users who have switched notifications off entirely, unless this message overrides
     * preferences.
     *
     * @param  array<int, int> $userIds
     * @return array<int, int>
     */
    private function filterEligibleUserIds(NotificationMessage $notificationMessage, array $userIds): array
    {
        if ($userIds === [] || $notificationMessage->bypasses_preferences) {
            return $userIds;
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('notifications_enabled', true)
            ->pluck('id')
            ->map(static fn($id): int => (int)$id)
            ->all();
    }

    /**
     * The channels this message may actually go out on: the ones its type allows that are also
     * registered in this application.
     *
     * @return array<int, NotificationChannelInterface>
     */
    private function channelsFor(NotificationMessage $notificationMessage): array
    {
        $supportedChannels = $notificationMessage->type->supportedChannels();

        return array_values(array_filter(
            $this->channels,
            static fn(NotificationChannelInterface $channel): bool => in_array($channel->channel(), $supportedChannels, true),
        ));
    }

    /**
     * @param array<int, int> $userIds
     */
    private function forgetUnreadCounts(array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->cacheService->set(sprintf(self::UNREAD_COUNT_CACHE_KEY_FORMAT, $userId), null, self::CACHE_TTL);
        }
    }

    private function refreshActiveGlobalsCache(): void
    {
        $this->cacheService->set(
            self::ACTIVE_GLOBALS_CACHE_KEY,
            $this->notificationMessageRepository->getActiveGlobalMessages(),
            self::CACHE_TTL,
        );
    }

    /**
     * The site-wide messages a user can still see and has not read.
     *
     * @return array<int, int>
     */
    private function getUnreadGlobalMessageIds(User $user): array
    {
        $candidateIds = [];

        foreach ($this->getActiveGlobalMessages() as $globalMessage) {
            if ($this->isGlobalVisible($user, Carbon::parse($globalMessage['created_at']), $globalMessage['game_version_id'], true)) {
                $candidateIds[] = (int)$globalMessage['id'];
            }
        }

        if ($candidateIds === []) {
            return [];
        }

        return array_values(array_diff(
            $candidateIds,
            $this->globalNotificationReadRepository->getReadMessageIdsForUser($user->id, $candidateIds),
        ));
    }

    /**
     * @param array<int, int> $readGlobalIds
     */
    private function isRead(User $user, NotificationMessage $notificationMessage, array $readGlobalIds): bool
    {
        if (!$notificationMessage->isGlobal()) {
            return $notificationMessage->getAttribute('delivery_read_at') !== null;
        }

        if (in_array($notificationMessage->id, $readGlobalIds, true)) {
            return true;
        }

        return $user->global_notifications_read_at !== null
            && $notificationMessage->created_at->lessThanOrEqualTo($user->global_notifications_read_at);
    }

    private function isGlobalVisibleTo(User $user, NotificationMessage $notificationMessage): bool
    {
        if ($notificationMessage->isExpired()) {
            return false;
        }

        return $this->isGlobalVisible($user, $notificationMessage->created_at, $notificationMessage->game_version_id, false);
    }

    private function isGlobalVisible(User $user, Carbon $createdAt, ?int $gameVersionId, bool $unreadOnly): bool
    {
        // A site-wide announcement is news, not history: an account created after it was sent never
        // sees it. Timestamps are second-precision, so the boundary is inclusive - signing up in
        // the same second an announcement goes out must not hide it
        if ($createdAt->lessThan($user->created_at)) {
            return false;
        }

        if ($gameVersionId !== null && $gameVersionId !== (int)$user->game_version_id) {
            return false;
        }

        return !($unreadOnly
            && $user->global_notifications_read_at !== null
            && $createdAt->lessThanOrEqualTo($user->global_notifications_read_at));
    }

    /**
     * @return array<int, array{id: int, created_at: string, game_version_id: int|null, expires_at: string|null}>
     */
    private function getActiveGlobalMessages(): array
    {
        $globalMessages = $this->cacheService->remember(
            self::ACTIVE_GLOBALS_CACHE_KEY,
            fn(): array => $this->notificationMessageRepository->getActiveGlobalMessages(),
            self::CACHE_TTL,
        );

        return is_array($globalMessages) ? $globalMessages : $this->notificationMessageRepository->getActiveGlobalMessages();
    }
}
