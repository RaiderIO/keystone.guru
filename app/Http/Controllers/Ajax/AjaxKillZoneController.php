<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\KillZone\KillZoneDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Controllers\Traits\ScopesToDungeonRoute;
use App\Http\Requests\KillZone\APIDeleteAllFormRequest;
use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Http\Requests\KillZone\APIKillZoneMassFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\User;
use App\Service\KillZone\Exceptions\KillZoneDeleteRefusedException;
use App\Service\KillZone\KillZoneServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Teapot\StatusCode\Http;

class AjaxKillZoneController extends Controller
{
    use ChangesDungeonRoute;
    use ScopesToDungeonRoute;

    /**
     * @return array<int, array<int, array{lat: float, lng: float, floor_id: int|null}>>
     */
    private function getKillZonePaths(KillZonePathServiceInterface $killZonePathService, DungeonRoute $dungeonRoute): array
    {
        $useFacade = $dungeonRoute->mappingVersion->facade_enabled &&
            User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;

        return $killZonePathService->calculateForRoute($dungeonRoute, $useFacade);
    }

    /**
     * @return array<string, mixed>
     */
    public function paths(
        KillZonePathServiceInterface $killZonePathService,
        DungeonRoute                 $dungeonRoute,
    ): array {
        Gate::authorize('view', $dungeonRoute);

        return [
            'killzone_paths' => $this->getKillZonePaths($killZonePathService, $dungeonRoute),
        ];
    }

    /**
     * Authorizes a kill zone create/update once, up front, before any storage side effect runs. An
     * existing kill zone must belong to the route named in the request.
     */
    private function authorizeKillZoneEdit(DungeonRoute $dungeonRoute, ?KillZone $killZone): void
    {
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $killZone);

        Gate::authorize('edit', $dungeonRoute);
    }

    /**
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function store(
        KillZoneServiceInterface     $killZoneService,
        KillZonePathServiceInterface $killZonePathService,
        APIKillZoneFormRequest       $request,
        DungeonRoute                 $dungeonRoute,
        ?KillZone                    $killZone = null,
    ): KillZone|Response {
        // Outside the try/catch on purpose: an authorization failure must surface as a 403, not be
        // rewritten into the 404 below.
        $this->authorizeKillZoneEdit($dungeonRoute, $killZone);

        try {
            $data = $request->validated();
            // Make sure that if we're unsetting all enemies from the killzone, it's handled differently
            // than mass-updating and not wanting to update the enemies at all
            if (!isset($data['enemies'])) {
                $data['enemies'] = [];
            }

            if (!isset($data['spells'])) {
                $data['spells'] = [];
            }

            $data['id'] = $killZone?->id ?? null; // @phpstan-ignore nullsafe.neverNull

            $isUpdate = $killZone !== null;

            // The pull's enemies and spells are written as a delete followed by an insert; without a
            // transaction a failure in between committed the delete and never ran the insert, so the
            // pull was permanently emptied (#4260). Retried because `kill_zone_enemies` is one of the
            // most contended tables in the app (#4239).
            $result = DB::transaction(fn(): KillZone => $killZoneService->saveKillZone(
                $dungeonRoute,
                $data,
                $isUpdate,
            ), 3);
        } catch (AuthorizationException|HttpException $deliberateResponse) {
            // A 403 or a 422 we raised on purpose must not be rewritten into the 404 below
            throw $deliberateResponse;
        } catch (Exception $exception) {
            report($exception);

            return response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        try {
            // Deliberately isolated from the save above: the kill zone is already persisted and
            // broadcast at this point, so a failure computing this cosmetic add-on must not be
            // reported back to the client as a total failure - the caller would never learn the
            // save actually succeeded and retry with an id-less payload, creating a duplicate.
            $result->setAttribute('killzone_paths', $this->getKillZonePaths($killZonePathService, $dungeonRoute));
        } catch (Exception $exception) {
            report($exception);

            $result->setAttribute('killzone_paths', []);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response|null
     *
     * @throws AuthorizationException
     */
    public function storeAll(
        KillZoneServiceInterface     $killZoneService,
        KillZonePathServiceInterface $killZonePathService,
        APIKillZoneMassFormRequest   $request,
        DungeonRoute                 $dungeonRoute,
    ) {
        // Fast-fail on the URL's own route before parsing the batch; each item below must then
        // belong to this route, since a batch entry's id could name a kill zone of any route.
        Gate::authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        // Set by the batch loop below so the response can name the pull that could not be saved,
        // even though the failure now has to travel out of the transaction as an exception
        $notFoundKillZoneId = null;
        // The id each submitted entry was saved to, in submission order - the only way a caller can
        // learn the id of an entry it submitted without one
        $killZoneIds = [];

        try {
            // One transaction for the whole batch, not one per pull: the bulk enemy delete at the
            // bottom spans every pull in the request, so a failure between it and the matching
            // insert used to permanently empty all of them at once (#4260). Retried because
            // `kill_zone_enemies` is one of the most contended tables in the app (#4239).
            $enemyForces = DB::transaction(function () use (
                $killZoneService,
                $dungeonRoute,
                $validated,
                &$notFoundKillZoneId,
                &$killZoneIds,
            ): int {
                // Update killzones, keyed by the submitted entry's position so the enemy phase
                // below can pair each entry with the row it was actually saved to - an entry that
                // names no id at all is created under a database-assigned one
                $killZones = new Collection();
                foreach ($validated['killzones'] ?? [] as $index => $killZoneData) {
                    try {
                        /** @var KillZone|null $existingKillZone */
                        $existingKillZone = isset($killZoneData['id'])
                            ? KillZone::find($killZoneData['id'])
                            : null;
                        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $existingKillZone);

                        // Unset the enemies since we're quicker to update that in bulk here
                        $kzDataWithoutEnemies = $killZoneData;
                        unset($kzDataWithoutEnemies['enemies']);
                        // Do not save the enemy forces - we save it one time down below
                        $killZones->put(
                            $index,
                            $killZoneService->saveKillZone(
                                $dungeonRoute,
                                $kzDataWithoutEnemies,
                                false,
                            ),
                        );
                    } catch (AuthorizationException|HttpException $deliberateResponse) {
                        // A 403 or a 422 we raised on purpose must not be rewritten into the 404 below
                        throw $deliberateResponse;
                    } catch (Exception $exception) {
                        $notFoundKillZoneId = $killZoneData['id'] ?? null;

                        throw $exception;
                    }
                }

                // Save enemy data at once and not one by one - it's slow
                $killZoneEnemies = [];
                $enemies         = $dungeonRoute->mappingVersion->enemies->keyBy('id');
                $validEnemyIds   = $enemies->pluck('id')->toArray();

                $killZoneIds = $killZones->pluck('id')->values()->toArray();

                // Insert new enemies based on what was sent
                foreach ($validated['killzones'] ?? [] as $index => $killZoneData) {
                    try {
                        if (isset($killZoneData['enemies'])) {
                            /** @var KillZone $savedKillZone */
                            $savedKillZone = $killZones->get($index);
                            $killZoneId    = $savedKillZone->id;

                            // Filter enemies - only allow those who are actually on the allowed floors (don't couple to enemies in other dungeons)
                            $killZoneDataEnemies = array_filter($killZoneData['enemies'], static fn(
                                $item,
                            ) => in_array($item, $validEnemyIds));

                            // Assign kill zone to each passed enemy
                            foreach ($killZoneDataEnemies as $killZoneDataEnemyId) {
                                $enemy             = $enemies->get($killZoneDataEnemyId);
                                $killZoneEnemies[] = [
                                    'kill_zone_id' => $killZoneId,
                                    'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
                                    'mdt_id'       => $enemy->mdt_id,
                                    'enemy_id'     => $enemy->id,
                                ];
                            }
                        }
                    } catch (Exception $exception) {
                        $notFoundKillZoneId = $killZoneData['id'] ?? null;

                        throw $exception;
                    }
                }

                // May be empty if the user did not send any enemies
                if ($killZoneEnemies !== []) {
                    // Delete existing enemies
                    KillZoneEnemy::whereIn('kill_zone_id', $killZones->pluck('id')->toArray())->delete();
                    // Save all new enemies at once
                    KillZoneEnemy::insert($killZoneEnemies);
                }

                $enemyForces = $dungeonRoute->getEnemyForces();

                // Written through the query builder, and the timestamp with it rather than via
                // touch(): $dungeonRoute outlives this closure, so on a retry Eloquent would find
                // the instance clean after the rolled-back first attempt and issue no SQL at all
                // (#4250)
                DungeonRoute::query()->whereKey($dungeonRoute->id)->update([
                    'enemy_forces' => $enemyForces,
                    'updated_at'   => Carbon::now(),
                ]);

                return $enemyForces;
            }, 3);
        } catch (AuthorizationException|HttpException $deliberateResponse) {
            // A 403 or a 422 we raised on purpose must not be rewritten into the 404 below
            throw $deliberateResponse;
        } catch (Exception $exception) {
            // The catch now also covers the enemy_forces write, which used to sit outside any
            // try/catch - a genuine database failure there would otherwise turn into a silent 404
            report($exception);

            return response(sprintf('Unable to find kill zone %s', $notFoundKillZoneId), Http::NOT_FOUND);
        }

        // Reflect the committed state on the in-memory instance for the response
        $dungeonRoute->setAttribute('enemy_forces', $enemyForces);

        return [
            'enemy_forces'   => $enemyForces,
            'killzone_ids'   => $killZoneIds,
            'killzone_paths' => $this->getKillZonePaths($killZonePathService, $dungeonRoute),
        ];
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws \Exception
     */
    public function delete(
        KillZoneServiceInterface     $killZoneService,
        KillZonePathServiceInterface $killZonePathService,
        Request                      $request,
        DungeonRoute                 $dungeonRoute,
        KillZone                     $killZone,
    ) {
        $this->abortUnlessBelongsToDungeonRoute($dungeonRoute, $killZone);

        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
        Gate::authorize('edit', $dungeonRoute);

        try {
            $enemyForces = $killZoneService->deleteKillZone($dungeonRoute, $killZone);
        } catch (KillZoneDeleteRefusedException $exception) {
            report($exception);

            return response(__('controller.killzone.error.unable_to_delete_pull'), Http::INTERNAL_SERVER_ERROR);
        } catch (Exception $exception) {
            report($exception);

            return response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        try {
            // Deliberately outside the transaction: this only reads, and holding row locks on
            // kill_zones/kill_zone_enemies/dungeon_routes while it walks the route's floors and
            // floor unions would widen the very lock window the retries above exist to absorb
            $result = [
                'enemy_forces'   => $enemyForces,
                'killzone_count' => $dungeonRoute->killZones()->count(),
                'killzone_paths' => $this->getKillZonePaths($killZonePathService, $dungeonRoute),
            ];
        } catch (Exception $exception) {
            report($exception);

            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|Application|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function deleteAll(
        KillZonePathServiceInterface $killZonePathService,
        APIDeleteAllFormRequest      $request,
        DungeonRoute                 $dungeonRoute,
    ) {
        Gate::authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        if ($validated['confirm'] === 'yes') {
            try {
                // Clearing a route is six writes per pull plus the route itself;
                // without a transaction a failure part-way through left the route
                // half-cleared, with no way for the client to tell how far it got (#4260)
                DB::transaction(function () use ($dungeonRoute): void {
                    // Queried rather than read off $dungeonRoute's relation: Model::delete() flips
                    // `exists` to false and that survives a rollback, so a retried attempt would
                    // iterate the same already-"deleted" instances, delete nothing, and still report
                    // the route as cleared (#4250)
                    $killZones = KillZone::query()
                        ->where('dungeon_route_id', $dungeonRoute->id)
                        ->get();

                    // Deleted one at a time - a mass delete on the relation skips KillZone::deleting, which
                    // is what cleans up the kill zone's enemies and spells
                    foreach ($killZones as $killZone) {
                        $killZone->delete();
                    }

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::user();
                        foreach ($killZones as $killZone) {
                            $this->dungeonRouteChanged($dungeonRoute, $killZone, null);
                        }

                        // afterCommit: collaborators must not be told the route was cleared while a
                        // rollback or a retried attempt can still put every pull back
                        DB::afterCommit(static function () use ($dungeonRoute, $user, $killZones): void {
                            foreach ($killZones as $killZone) {
                                try {
                                    broadcast(new KillZoneDeletedEvent($dungeonRoute, $user, $killZone));
                                } catch (BroadcastException) {
                                    // Ignore broadcast failures
                                }
                            }
                        });
                    }

                    $dungeonRoute->load('killZones');

                    // Query builder, timestamp included rather than touch(): $dungeonRoute outlives
                    // this closure, so on a retry Eloquent would find it clean and write nothing (#4250)
                    DungeonRoute::query()->whereKey($dungeonRoute->id)->update([
                        'enemy_forces' => 0,
                        'updated_at'   => Carbon::now(),
                    ]);
                    $dungeonRoute->setAttribute('enemy_forces', 0);
                }, 3);

                // Deliberately outside the transaction: this only reads, and holding row locks on
                // kill_zones/kill_zone_enemies/dungeon_routes while it walks the route's floors and
                // floor unions would widen the very lock window the retries above exist to absorb
                $result = [
                    'enemy_forces'   => 0,
                    'killzone_paths' => $this->getKillZonePaths($killZonePathService, $dungeonRoute),
                ];
            } catch (\Exception $exception) {
                report($exception);

                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
            }
        } else {
            $result = response('You must confirm before deleting all pulls', Http::BAD_REQUEST);
        }

        return $result;
    }
}
