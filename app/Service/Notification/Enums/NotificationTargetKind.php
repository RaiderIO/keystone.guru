<?php

namespace App\Service\Notification\Enums;

enum NotificationTargetKind: string
{
    case UserIds = 'user_ids';

    case Role = 'role';

    case Everyone = 'everyone';
}
