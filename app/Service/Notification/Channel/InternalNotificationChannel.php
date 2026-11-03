<?php

namespace App\Service\Notification\Channel;

use App\Models\Notification\Enums\NotificationChannel;
use App\Models\Notification\NotificationMessage;
use App\Repositories\Interfaces\Notification\UserNotificationRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Delivers to the in-app feed by writing one delivery row per user.
 */
class InternalNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly UserNotificationRepositoryInterface $userNotificationRepository,
    ) {
    }

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Internal;
    }

    /**
     * {@inheritDoc}
     */
    public function deliver(NotificationMessage $notificationMessage, array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $this->userNotificationRepository->insertDeliveries($notificationMessage->id, $userIds, Carbon::now());
    }
}
