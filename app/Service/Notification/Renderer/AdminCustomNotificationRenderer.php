<?php

namespace App\Service\Notification\Renderer;

use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use App\Service\Notification\Dtos\NotificationContent;

/**
 * Renders an admin's free-text announcement.
 *
 * The body is passed through untranslated in whatever language it was typed in; only the title is
 * localized. Tags are already stripped when the message is stored, so the body is plain text.
 */
class AdminCustomNotificationRenderer implements NotificationRendererInterface
{
    private const array SUPPORTED_TYPES = [NotificationType::AdminCustom];

    public function supports(NotificationType $type): bool
    {
        return in_array($type, self::SUPPORTED_TYPES, true);
    }

    public function render(NotificationMessage $notificationMessage, string $locale): NotificationContent
    {
        return new NotificationContent(
            __($notificationMessage->type->titleLangKey(), [], $locale),
            (string)$notificationMessage->body,
        );
    }
}
