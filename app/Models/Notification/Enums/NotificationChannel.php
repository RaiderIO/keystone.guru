<?php

namespace App\Models\Notification\Enums;

/**
 * A way a notification can reach a user. Which channels a given notification may use is decided by
 * {@see NotificationType::supportedChannels()}, never by configuration.
 */
enum NotificationChannel: string
{
    /** The in-app feed and the unread badge. */
    case Internal = 'internal';

    case Email = 'email';
}
