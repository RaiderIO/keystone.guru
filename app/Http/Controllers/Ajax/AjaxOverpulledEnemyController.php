<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ScopesToDungeonRoute;
use App\Http\Requests\OverpulledEnemy\OverpulledEnemyFormRequest;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\User;
use App\Service\LiveSession\Dtos\LiveSessionStateBroadcastResult;
use App\Service\LiveSession\LiveSessionCombatStateServiceInterface;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

class AjaxOverpulledEnemyController extends Controller
{
    use ScopesToDungeonRoute;

    /**
     * @return array<string, mixed>|Response
     *
     * @throws AuthorizationException
     *
     * @deprecated Overpulls are detected from the combat log by {@see \App\Service\LiveSession\LiveSessionOverpullDetectionService}.
     */
    public function store(
        OverpulledEnemyServiceInterface        $overpulledEnemyService,
        LiveSessionStateServiceInterface       $liveSessionStateService,
        LiveSessionCombatStateServiceInterface $combatStateService,
        OverpulledEnemyFormRequest             $request,
        DungeonRoute                           $dungeonRoute,
        LiveSession                            $liveSession,
    ) {
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $liveSession);

        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('view', $liveSession);

        $validated  = $request->validated();
        $killZoneId = $request->getKillZone()->id;

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        try {
            $overpulledEnemyService->withOverpullLock($liveSession, static fn() => DB::transaction(static function () use ($enemies, $liveSession, $killZoneId, $combatStateService): void {
                foreach ($enemies as $enemy) {
                    /** @var LiveSessionOverpulledEnemy $overpulledEnemy */
                    $overpulledEnemy = LiveSessionOverpulledEnemy::where('live_session_id', $liveSession->id)
                        ->where('npc_id', $enemy->npc_id)
                        ->where('mdt_id', $enemy->mdt_id)
                        ->firstOrNew([
                            'live_session_id' => $liveSession->id,
                            'kill_zone_id'    => $killZoneId,
                            'npc_id'          => $enemy->npc_id,
                            'mdt_id'          => $enemy->mdt_id,
                        ], [
                            'enemy_id' => $enemy->id,
                        ]);

                    if (!$overpulledEnemy->save()) {
                        throw new Exception('Unable to save overpulled enemy!');
                    }

                    $combatStateService->clearOverpullDismissed($liveSession, $enemy->npc_id, $enemy->mdt_id);
                }
            }));
        } catch (LockTimeoutException) {
            return self::liveSessionBusyResponse();
        }

        // Broadcast only once the batch is committed, so no listener can read pre-commit state
        $this->broadcastState($liveSessionStateService, $liveSession);

        return $overpulledEnemyService->getRouteCorrection($liveSession)->toArray();
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws AuthorizationException
     *
     * @deprecated Overpulls are detected from the combat log by {@see \App\Service\LiveSession\LiveSessionOverpullDetectionService}.
     */
    public function delete(
        OverpulledEnemyServiceInterface        $overpulledEnemyService,
        LiveSessionStateServiceInterface       $liveSessionStateService,
        LiveSessionCombatStateServiceInterface $combatStateService,
        OverpulledEnemyFormRequest             $request,
        DungeonRoute                           $dungeonRoute,
        LiveSession                            $liveSession,
    ) {
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $liveSession);

        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('view', $liveSession);

        $result = response()->noContent();

        $validated = $request->validated();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        try {
            // The whole batch is one user action - undoing an overpull. Without a transaction a
            // failure on the third enemy committed the first two, leaving the live session showing
            // half a pull while the client was told the request failed
            $overpulledEnemyService->withOverpullLock($liveSession, static fn() => DB::transaction(static function () use ($enemies, $liveSession, $combatStateService): void {
                foreach ($enemies as $enemy) {
                    /** @var LiveSessionOverpulledEnemy|null $overpulledEnemy */
                    $overpulledEnemy = LiveSessionOverpulledEnemy::where('live_session_id', $liveSession->id)
                        ->where('npc_id', $enemy->npc_id)
                        ->where('mdt_id', $enemy->mdt_id)
                        ->first();

                    if ($overpulledEnemy === null) {
                        continue;
                    }

                    $overpulledEnemy->delete();
                    $combatStateService->setOverpullDismissed($liveSession, $enemy->npc_id, $enemy->mdt_id);
                }
            }));

            // Broadcast only once the batch is committed, so no listener can read pre-commit state
            $this->broadcastState($liveSessionStateService, $liveSession);

            // Optionally, don't calculate the return value. Computed once after the loop, not per enemy -
            // it already reflects every deletion above, so recomputing per iteration is redundant work.
            if ($enemies->isNotEmpty() && !(bool)($validated['no_result'] ?? false)) {
                $result = $overpulledEnemyService->getRouteCorrection($liveSession)->toArray();
            }
        } catch (LockTimeoutException) {
            $result = self::liveSessionBusyResponse();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    private function broadcastState(LiveSessionStateServiceInterface $liveSessionStateService, LiveSession $liveSession): void
    {
        if (!Auth::check()) {
            return;
        }

        /** @var User $user */
        $user = Auth::getUser();

        // The job delivers the state it finds undelivered, and releases itself to try again while that keeps failing
        if ($liveSessionStateService->broadcastState($liveSession, $user) === LiveSessionStateBroadcastResult::Failed) {
            ProcessLiveSessionCombatLogBuffer::dispatch($liveSession->id);
        }
    }

    /**
     * Overpull detection or another viewer's edit held the session's overpulls for longer than the wait; nothing was
     * changed, so the client may simply try again.
     */
    private static function liveSessionBusyResponse(): Response
    {
        return response(__('controller.overpulledenemy.error.live_session_busy'), Http::CONFLICT);
    }
}
