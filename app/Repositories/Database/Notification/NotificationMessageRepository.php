<?php

namespace App\Repositories\Database\Notification;

use App\Models\Notification\Enums\NotificationAudience;
use App\Models\Notification\NotificationMessage;
use App\Models\User;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Notification\NotificationMessageRepositoryInterface;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

class NotificationMessageRepository extends DatabaseRepository implements NotificationMessageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(NotificationMessage::class);
    }

    /**
     * {@inheritDoc}
     */
    public function firstOrCreateByDedupeKey(string $dedupeKey, array $attributes): NotificationMessage
    {
        return NotificationMessage::query()->firstOrCreate(['dedupe_key' => $dedupeKey], $attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveGlobalMessages(): array
    {
        return NotificationMessage::query()
            ->where('audience', NotificationAudience::Global)
            ->where($this->unexpired())
            ->orderByDesc('created_at')
            ->get(['id', 'created_at', 'game_version_id', 'expires_at'])
            ->map(static fn(NotificationMessage $message): array => [
                'id'              => $message->id,
                'created_at'      => $message->created_at->toIso8601String(),
                'game_version_id' => $message->game_version_id,
                'expires_at'      => $message->expires_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetedFeedPage(int $userId, int $limit, ?NotificationMessage $cursor): EloquentCollection
    {
        $query = NotificationMessage::query()
            ->join('user_notifications', 'user_notifications.notification_message_id', '=', 'notification_messages.id')
            ->where('user_notifications.user_id', $userId)
            ->select('notification_messages.*', 'user_notifications.read_at as delivery_read_at');

        return $this->applyCursor($query, $cursor)
            ->orderByDesc('notification_messages.created_at')
            ->orderByDesc('notification_messages.id')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalFeedPage(User $user, int $limit, ?NotificationMessage $cursor): EloquentCollection
    {
        $query = NotificationMessage::query()
            ->select('notification_messages.*')
            ->where('notification_messages.audience', NotificationAudience::Global)
            ->where($this->unexpired('notification_messages.expires_at'))
            // A site-wide announcement is news, not history: an account created after it was sent
            // never sees it. Second-precision timestamps make the boundary inclusive, matching
            // NotificationService's unread check
            ->where('notification_messages.created_at', '>=', $user->created_at)
            ->where(static fn(Builder|QueryBuilder $gameVersionQuery) => $gameVersionQuery
                ->whereNull('notification_messages.game_version_id')
                ->orWhere('notification_messages.game_version_id', $user->game_version_id));

        return $this->applyCursor($query, $cursor)
            ->orderByDesc('notification_messages.created_at')
            ->orderByDesc('notification_messages.id')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentWithDeliveryCounts(int $limit): EloquentCollection
    {
        return NotificationMessage::query()
            ->with('createdBy')
            ->withCount([
                'userNotifications as delivered_count',
                'userNotifications as read_count' => static fn(Builder|QueryBuilder $query) => $query->whereNotNull('read_at'),
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Builder<NotificationMessage> $query
     * @return Builder<NotificationMessage>
     */
    private function applyCursor(Builder $query, ?NotificationMessage $cursor): Builder
    {
        if ($cursor === null) {
            return $query;
        }

        return $query->where(static fn(Builder|QueryBuilder $cursorQuery) => $cursorQuery
            ->where('notification_messages.created_at', '<', $cursor->created_at)
            ->orWhere(static fn(Builder|QueryBuilder $tieQuery) => $tieQuery
                ->where('notification_messages.created_at', $cursor->created_at)
                ->where('notification_messages.id', '<', $cursor->id)));
    }

    /**
     * @return \Closure(Builder<NotificationMessage>|QueryBuilder): void
     */
    private function unexpired(string $column = 'expires_at'): \Closure
    {
        return static function (Builder|QueryBuilder $query) use ($column): void {
            $query->whereNull($column)->orWhere($column, '>', Carbon::now());
        };
    }
}
