<?php

namespace App\Http\Controllers\Ajax;

use App\Events\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\OverpulledEnemy\OverpulledEnemyDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\OverpulledEnemy\OverpulledEnemyFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\LiveSession;
use App\Models\User;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Teapot\StatusCode\Http;

class AjaxOverpulledEnemyController extends Controller
{
    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function store(
        OverpulledEnemyServiceInterface $overpulledEnemyService,
        OverpulledEnemyFormRequest      $request,
        DungeonRoute                    $dungeonRoute,
        LiveSession                     $liveSession,
    ) {
        Gate::authorize('view', $dungeonRoute);
        Gate::authorize('view', $liveSession);

        $validated = $request->validated();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        /** @var array<int, array{OverpulledEnemy, Enemy}> $savedEnemies */
        $savedEnemies = DB::transaction(function () use ($enemies, $liveSession, $validated): array {
            $savedEnemies = [];

            foreach ($enemies as $enemy) {
                /** @var OverpulledEnemy $overpulledEnemy */
                $overpulledEnemy = OverpulledEnemy::where('live_session_id', $liveSession->id)
                    ->where('npc_id', $enemy->npc_id)
                    ->where('mdt_id', $enemy->mdt_id)
                    ->firstOrNew([
                        'live_session_id' => $liveSession->id,
                        'kill_zone_id'    => $validated['kill_zone_id'],
                        'npc_id'          => $enemy->npc_id,
                        'mdt_id'          => $enemy->mdt_id,
                    ]);

                if (!$overpulledEnemy->save()) {
                    throw new Exception('Unable to save overpulled enemy!');
                }

                $savedEnemies[] = [$overpulledEnemy, $enemy];
            }

            return $savedEnemies;
        });

        // Broadcast only once the batch is committed, so no listener can read pre-commit state
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::getUser();

            foreach ($savedEnemies as [$overpulledEnemy, $enemy]) {
                try {
                    broadcast(new OverpulledEnemyChangedEvent($liveSession, $user, $overpulledEnemy, $enemy));
                } catch (BroadcastException) {
                    // Ignore broadcast failures
                }
            }
        }

        return $overpulledEnemyService->getRouteCorrection($liveSession)->toArray();
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function delete(
        OverpulledEnemyServiceInterface $overpulledEnemyService,
        OverpulledEnemyFormRequest      $request,
        DungeonRoute                    $dungeonroute,
        LiveSession                     $livesession,
    ) {
        Gate::authorize('view', $dungeonroute);
        Gate::authorize('view', $livesession);

        $result = response()->noContent();

        $validated = $request->validated();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::whereIn('id', $validated['enemy_ids'])->get();

        try {
            // The whole batch is one user action - undoing an overpull. Without a transaction a
            // failure on the third enemy committed the first two, leaving the live session showing
            // half a pull while the client was told the request failed
            /** @var array<int, Enemy> $deletedEnemies */
            $deletedEnemies = DB::transaction(function () use ($enemies, $livesession): array {
                $deletedEnemies = [];

                foreach ($enemies as $enemy) {
                    /** @var OverpulledEnemy|null $overpulledEnemy */
                    $overpulledEnemy = OverpulledEnemy::where('live_session_id', $livesession->id)
                        ->where('npc_id', $enemy->npc_id)
                        ->where('mdt_id', $enemy->mdt_id)
                        ->first();

                    if ($overpulledEnemy !== null && $overpulledEnemy->delete() === true) {
                        $deletedEnemies[] = $enemy;
                    }
                }

                return $deletedEnemies;
            });

            // Broadcast only once the batch is committed, so no listener can read pre-commit state
            if (Auth::check()) {
                /** @var User $user */
                $user = Auth::getUser();

                foreach ($deletedEnemies as $enemy) {
                    try {
                        broadcast(new OverpulledEnemyDeletedEvent($livesession, $user, $enemy));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }
            }

            // The route correction is a read that only shapes the response - it used to be
            // recomputed once per enemy inside the loop, which produced the same value every time.
            // Optionally, don't calculate the return value
            if ($enemies->isNotEmpty() && $validated['no_result'] !== true) {
                $result = $overpulledEnemyService->getRouteCorrection($livesession)->toArray();
            }
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
