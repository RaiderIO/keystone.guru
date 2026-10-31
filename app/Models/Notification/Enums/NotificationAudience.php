<?php

namespace App\Models\Notification\Enums;

/**
 * Who a notification message is addressed to, which decides how its delivery and read state are stored.
 */
enum NotificationAudience: string
{
    /** Addressed to specific users, each of whom gets their own delivery row. */
    case User = 'user';

    /**
     * Addressed to every registered user. Stored as a single row with no delivery rows at all, so a
     * site-wide announcement costs one INSERT no matter how many accounts exist. Read state is
     * written lazily, only once a user actually reads it.
     */
    case Global = 'global';
}
