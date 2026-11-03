<?php

namespace App\Service\Notification\Dtos;

use App\Models\Notification\Enums\NotificationType;
use Illuminate\Support\Carbon;

/**
 * One notification as a reader sees it: text resolved in their locale, with their own read state.
 */
readonly class RenderedNotification
{
    public function __construct(
        public int              $notificationMessageId,
        public NotificationType $type,
        public string           $title,
        public string           $body,
        public ?string          $url,
        public string           $icon,
        public Carbon           $createdAt,
        public bool             $read,
    ) {
    }
}
