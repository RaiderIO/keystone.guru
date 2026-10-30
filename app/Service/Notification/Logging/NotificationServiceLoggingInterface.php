<?php

namespace App\Service\Notification\Logging;

interface NotificationServiceLoggingInterface
{
    public function createMessageDeduplicated(string $dedupeKey, int $notificationMessageId): void;

    public function deliverDispatchedToQueue(int $notificationMessageId, int $userCount): void;

    public function deliverInline(int $notificationMessageId, int $userCount): void;

    public function markReadNotVisible(int $notificationMessageId, int $userId): void;

    public function retract(int $notificationMessageId, int $deliveryCount): void;
}
