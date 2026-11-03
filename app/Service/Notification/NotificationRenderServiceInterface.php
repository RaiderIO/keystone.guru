<?php

namespace App\Service\Notification;

use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use App\Service\Notification\Dtos\RenderedNotification;

interface NotificationRenderServiceInterface
{
    /**
     * Renders one notification for a reader, in their locale and with their read state.
     */
    public function render(NotificationMessage $notificationMessage, string $locale, bool $read): RenderedNotification;

    /**
     * Whether a renderer exists for the given type. Every type must have one, which
     * NotificationTypeTest asserts for every case of the enum.
     */
    public function hasRendererFor(NotificationType $type): bool;
}
