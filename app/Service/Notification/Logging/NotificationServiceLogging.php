<?php

namespace App\Service\Notification\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class NotificationServiceLogging extends StructuredLogging implements NotificationServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function createMessageDeduplicated(string $dedupeKey, int $notificationMessageId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function deliverDispatchedToQueue(int $notificationMessageId, int $userCount): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function deliverInline(int $notificationMessageId, int $userCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function markReadNotVisible(int $notificationMessageId, int $userId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function retract(int $notificationMessageId, int $deliveryCount): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
