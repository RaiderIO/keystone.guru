<?php

namespace App\Service\Notification\Renderer;

use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use App\Service\Notification\Dtos\NotificationContent;

/**
 * Turns a stored notification into text for one locale.
 *
 * Rendering happens when a feed is read rather than when the notification is sent, so a message
 * reads in each recipient's own language. A renderer therefore resolves the ids in the message's
 * params itself instead of trusting any text stored alongside them.
 */
interface NotificationRendererInterface
{
    public function supports(NotificationType $type): bool;

    public function render(NotificationMessage $notificationMessage, string $locale): NotificationContent;
}
