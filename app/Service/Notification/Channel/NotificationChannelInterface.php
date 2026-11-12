<?php

namespace App\Service\Notification\Channel;

use App\Models\Notification\Enums\NotificationChannel;
use App\Models\Notification\NotificationMessage;

/**
 * A way of getting one notification to a set of users.
 *
 * Delivery is defined in bulk rather than per user, so a channel that batches (anything sending
 * mail, for instance) can be added without the surrounding code changing shape.
 */
interface NotificationChannelInterface
{
    public function channel(): NotificationChannel;

    /**
     * @param array<int, int> $userIds
     */
    public function deliver(NotificationMessage $notificationMessage, array $userIds): void;
}
