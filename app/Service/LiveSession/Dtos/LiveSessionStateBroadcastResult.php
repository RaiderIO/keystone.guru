<?php

namespace App\Service\LiveSession\Dtos;

enum LiveSessionStateBroadcastResult
{
    /** The state was delivered and remembered as the last one delivered */
    case Sent;

    /** The state matches the last one delivered, so nothing was sent */
    case Unchanged;

    /** Sending the state threw; nothing was remembered, so the next attempt sends it again */
    case Failed;
}
