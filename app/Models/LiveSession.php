<?php

namespace App\Models;

use App\Models\LiveSession\LiveSession as NamespacedLiveSession;

/**
 * Queued payloads of the previous release (broadcast events carrying a live session as their context) name this
 * class; it lets them unserialize after the deploy.
 *
 * @deprecated Use {@see NamespacedLiveSession}. Removed one release after the move.
 */
class LiveSession extends NamespacedLiveSession
{
}
