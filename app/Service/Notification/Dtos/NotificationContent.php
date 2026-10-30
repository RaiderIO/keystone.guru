<?php

namespace App\Service\Notification\Dtos;

/**
 * A notification's human-readable text, resolved for one specific locale.
 */
readonly class NotificationContent
{
    public function __construct(
        public string $title,
        public string $body,
    ) {
    }
}
