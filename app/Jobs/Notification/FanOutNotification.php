<?php

namespace App\Jobs\Notification;

use App\Jobs\Enums\QueueName;
use App\Models\Notification\NotificationMessage;
use App\Service\Notification\NotificationServiceInterface;
use App\Service\Notification\NotificationTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Delivers one notification to a large audience, in chunks.
 *
 * The job carries the audience as a {@see NotificationTarget} description and rebuilds the query
 * itself, so its payload stays a few bytes whether it covers ten users or a hundred thousand, and a
 * retry after a partial failure simply re-runs - the unique index on the delivery table makes
 * repeating a chunk a no-op.
 */
class FanOutNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const int CHUNK_SIZE = 1000;

    public int $timeout = 900;

    public function __construct(
        private readonly int                $notificationMessageId,
        private readonly NotificationTarget $target,
    ) {
        $this->queue = QueueName::LongRunning->queueName();
    }

    public function handle(NotificationServiceInterface $notificationService): void
    {
        $notificationMessage = NotificationMessage::query()->find($this->notificationMessageId);

        if ($notificationMessage === null) {
            Log::channel('scheduler')->warning('Not fanning out notification; it no longer exists', [
                'notification_message_id' => $this->notificationMessageId,
            ]);

            return;
        }

        $this->target->newUserIdQuery()->chunkById(
            self::CHUNK_SIZE,
            static function (Collection $users) use ($notificationService, $notificationMessage): void {
                $notificationService->deliverToUserIds($notificationMessage, $users->pluck('id')->map(static fn($id): int => (int)$id)->all());
            },
            'users.id',
            'id',
        );
    }
}
