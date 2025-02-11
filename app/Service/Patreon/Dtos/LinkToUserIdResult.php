<?php

namespace App\Service\Patreon\Dtos;

enum LinkToUserIdResult: int
{
    case LinkSuccessful        = 1;
    case PatreonErrorOccurred  = 10;
    case InternalErrorOccurred = 20;
    case PatreonSessionExpired = 30;
}
