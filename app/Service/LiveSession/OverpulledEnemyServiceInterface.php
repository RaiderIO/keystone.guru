<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use Illuminate\Contracts\Cache\LockTimeoutException;

interface OverpulledEnemyServiceInterface
{
    public function getRouteCorrection(LiveSession $liveSession): DungeonRouteCorrection;

    /**
     * Run the callable while holding the live session's overpull lock. Everything that reads or writes the session's
     * overpulls and overpull dismissals together holds it, so a viewer taking an overpull back cannot slip in between
     * overpull detection reading the dismissals and writing the overpulls.
     *
     * @template TResult
     *
     * @param callable(): TResult $callable
     *
     * @return TResult
     *
     * @throws LockTimeoutException When the lock is not free within keystoneguru.live_sessions.overpull_lock_wait_seconds
     */
    public function withOverpullLock(LiveSession $liveSession, callable $callable): mixed;
}
