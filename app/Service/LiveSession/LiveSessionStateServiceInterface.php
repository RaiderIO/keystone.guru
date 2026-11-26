<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\User;
use App\Service\LiveSession\Dtos\LiveSessionStateBroadcastResult;

/**
 * The live state of a live session in the one shape both the page load (map context) and the broadcast carry.
 *
 * Enemy id lists are sorted ascending and delta-encoded: the first entry is an enemy id, every next entry the
 * difference to the previous id. Player coordinates are `[lat, lng, floor_id]`, once for split floors and once
 * for the facade, so every viewer picks the set matching its own facade setting.
 *
 * @phpstan-type LiveSessionStatePlayer array{
 *     guid: string,
 *     name: string,
 *     class_id: int|null,
 *     specialization_id: int|null,
 *     specialization_icon_url: string|null,
 *     split_floors: array{0: float, 1: float, 2: int|null},
 *     facade: array{0: float, 1: float, 2: int|null}
 * }
 * @phpstan-type LiveSessionState array{
 *     killed: array<int, int>,
 *     in_combat: array<int, int>,
 *     obsolete: array<int, int>,
 *     overpulled: array<int, array{0: int, 1: int}>,
 *     players: array<int, LiveSessionStatePlayer>
 * }
 */
interface LiveSessionStateServiceInterface
{
    /**
     * @return LiveSessionState
     */
    public function getState(LiveSession $liveSession): array;

    /**
     * Broadcast the current state to everyone viewing the session, and remember it as the last one delivered.
     *
     * @return LiveSessionStateBroadcastResult Sent or Failed
     */
    public function broadcastState(LiveSession $liveSession, User $user): LiveSessionStateBroadcastResult;

    /**
     * Broadcast the current state only when it differs from the last state delivered for this session. Without a
     * remembered state (never broadcast, the cache lost it, or the last broadcast failed) it is broadcast.
     */
    public function broadcastStateIfChanged(LiveSession $liveSession, User $user): LiveSessionStateBroadcastResult;
}
