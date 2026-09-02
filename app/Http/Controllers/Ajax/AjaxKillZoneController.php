<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\KillZone\KillZoneChangedEvent;
use App\Events\Models\KillZone\KillZoneDeletedEvent;
use App\Events\Models\PridefulEnemy\PridefulEnemyDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Http\Requests\KillZone\APIDeleteAllFormRequest;
use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Http\Requests\KillZone\APIKillZoneMassFormRequest;
use App\Jobs\RefreshEnemyForces;
use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;
use App\Models\Enemies\PridefulEnemy;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
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
    use EnforcesDungeonRouteLimits;
    use ChangesDungeonRoute;

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
     * Authorizes a kill zone create/update against its real dungeon route - the kill zone's own
     * route if it already exists and isn't a sandbox, not just the route named in the request -
     * so the ownership check happens once, up front, before any storage side effect runs, rather
     * than saveKillZone() repeating the same check on the same route for every non-hijack call.
     *
     * @return DungeonRoute the route to actually save the kill zone against
     */
    private function authorizeKillZoneEdit(DungeonRoute $dungeonRoute, ?KillZone $killZone): DungeonRoute
    {
        if ($killZone?->dungeonRoute !== null && !$killZone->dungeonRoute->isSandbox()) {
            Gate::authorize('edit', $killZone->dungeonRoute);

            return $killZone->dungeonRoute;
        }

        Gate::authorize('edit', $dungeonRoute);

        return $dungeonRoute;
    }

    /**
     * Rewrites a submitted kill zone location that is expressed on a facade (combined multi-floor)
     * map onto the real floor it belongs to, mutating $data so the conversion happens *before* the
     * row is written rather than as a second update afterwards.
     *
     * Whether the submitted location is a facade location is derived from the submitted floor's own
     * `facade` flag, deliberately not from the acting user's map facade style: that style is
     * request-scoped state which the save request does not necessarily share with the request that
     * rendered the map the location was placed on, and a mismatch used to persist the facade floor
     * verbatim (#3917).
     *
     * @throws HttpException when a facade location on a facade-rendering route belongs to no real floor
     *
     * @param  array<string, mixed> $data
     * @return LatLng|null          the location exactly as submitted, so the response can echo it back in the
     *                              plane the client is rendering, or null if no facade location was
     *                              submitted or the submitted one was dropped
     */
    private function convertSubmittedFacadeLocation(
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        KillZone                    $killZone,
        array                      &                       $data,
    ): ?LatLng {
        if (!isset($data['floor_id'], $data['lat'], $data['lng'])) {
            return null;
        }

        /** @var Floor|null $submittedFloor */
        $submittedFloor = Floor::find($data['floor_id']);

        if ($submittedFloor === null || !$submittedFloor->facade) {
            return null;
        }

        // The route's map never renders a facade, so this cannot be a location the user just placed
        // on one - it is a bad location echoed back by a client that loaded an already-corrupted row
        // (convertFacadeMapLocationToMapLocation() would hand it straight back unconverted anyway).
        // Drop it so the save still succeeds: failing here would make every such pull uneditable.
        if (!$dungeonRoute->mappingVersion->facade_enabled) {
            $data['floor_id'] = null;
            $data['lat']      = null;
            $data['lng']      = null;

            return null;
        }

        $submittedLatLng = new LatLng((float)$data['lat'], (float)$data['lng'], $submittedFloor);

        $convertedLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
            $dungeonRoute->mappingVersion,
            $submittedLatLng,
        );

        $convertedFloor = $convertedLatLng->getFloor();

        // The location falls outside every floor union area, so it belongs to no real floor at all -
        // the dead space of the combined image (0.003% of it, measured across every facade dungeon).
        // It may never be persisted: every consumer downstream (ingame coordinate conversion, kill
        // zone paths, MDT export) assumes a real floor.
        if ($convertedFloor === null || $convertedFloor->facade) {
            // A location the client is merely echoing back unchanged comes from a row corrupted
            // before this fix, and refusing it would make that pull uneditable forever. Drop it and
            // let the save land - the KillZonePathService guard covers the row until it is re-placed.
            if (!$this->isNewLocationForKillZone($killZone, $data)) {
                $data['floor_id'] = null;
                $data['lat']      = null;
                $data['lng']      = null;

                return null;
            }

            // A location the user just placed is worth failing on, so they get feedback instead of
            // watching their marker silently disappear. 422 as a literal, like
            // abortIfDungeonRouteLimitReached() - Teapot's Http does not expose UNPROCESSABLE_ENTITY
            //
            // Expected to be rare (see the dead-space note above), not impossible - reported with the
            // submitted lat/lng so an occurrence can actually be plotted against the mapping version's
            // floor unions instead of only naming which one was hit.
            report(new Exception(sprintf(
                'Facade location (%s, %s) could not be converted to a real floor for mapping version %d, submitted floor %d',
                $data['lat'],
                $data['lng'],
                $dungeonRoute->mappingVersion->id,
                $submittedFloor->id,
            )));

            abort(422, __('controller.killzone.error.facade_location_not_convertible'));
        }

        $data['floor_id'] = $convertedFloor->id;
        $data['lat']      = $convertedLatLng->getLat();
        $data['lng']      = $convertedLatLng->getLng();

        return $submittedLatLng;
    }

    /**
     * Whether the submitted location differs from the one the kill zone already holds, i.e. whether
     * the user actually placed or moved the kill area rather than the client echoing back what it
     * loaded. Compared with a tolerance because the coordinates round-trip through JSON as strings.
     *
     * @param array<string, mixed> $data
     */
    private function isNewLocationForKillZone(KillZone $killZone, array $data): bool
    {
        if (!$killZone->exists || $killZone->floor_id === null) {
            return true;
        }

        return (int)$killZone->floor_id !== (int)$data['floor_id'] ||
            abs((float)$killZone->lat - (float)$data['lat']) > 0.0001 ||
            abs((float)$killZone->lng - (float)$data['lng']) > 0.0001;
    }

    /**
     * Deliberately opens no transaction of its own - every caller wraps it in one, so that
     * storeAll()'s batch is atomic as a whole rather than per pull, and so that a retry after a
     * deadlock re-runs the entire unit instead of a savepoint MySQL has already discarded. Every
     * model it touches is (re)hydrated inside this method, so a retried attempt starts clean.
     *
     * @throws \Exception
     *
     * @param array<string, mixed> $data
     */
    private function saveKillZone(
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonroute,
        array                       $data,
        bool                        $recalculateEnemyForces = true,
    ): KillZone {
        $enemyIds = $data['enemies'] ?? null;
        unset($data['enemies']);
        $data['dungeon_route_id'] = $dungeonroute->id;

        $spellIds = $data['spells'] ?? null;

        /** @var KillZone $killZone */
        $killZone = KillZone::with('dungeonRoute')->findOrNew($data['id'] ?? null);

        // Ownership is already authorized by authorizeKillZoneEdit() before this is called - just
        // resolve the real route the caller's authorization already resolved to.
        $dungeonroute = $killZone->dungeonRoute ?? $dungeonroute;

        $this->abortIfDungeonRouteLimitReached($dungeonroute, DungeonRouteLimitType::KillZones);

        // Must happen before the row is written - the database may never hold a facade floor
        $submittedFacadeLatLng = $this->convertSubmittedFacadeLocation($coordinatesService, $dungeonroute, $killZone, $data);

        $beforeModel = clone $killZone;

        // Capture the before-state enemy IDs before any delete/insert
        $beforeEnemyIds = $beforeModel->killZoneEnemies->pluck('enemy_id');

        if (!$killZone->exists) {
            // The primary key is always assigned by the database, never taken from the request
            unset($data['id']);
            $killZone = KillZone::create($data);
            $success  = true;
        } else {
            $success = $killZone->update($data);
        }

        if ($success) {
            // Only when the enemies are actually set
            if ($enemyIds !== null) {
                $killZone->killZoneEnemies()->delete();

                // Store them, but only if the enemies are part of the same dungeon as the dungeonroute
                $validEnemyIds   = [];
                $killZoneEnemies = [];
                $enemyModels     = $dungeonroute->mappingVersion->enemies()->whereIn('id', $enemyIds)->get();
                foreach ($enemyIds as $enemyId) {
                    /** @var Enemy|null $enemy */
                    $enemy = $enemyModels->where('id', $enemyId)->first();
                    // Could be if someone decides to send an enemy ID that is not part of the current mapping version
                    if ($enemy === null) {
                        continue;
                    }

                    // Assign kill zone to each passed enemy
                    $killZoneEnemies[] = [
                        'kill_zone_id' => $killZone->id,
                        'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
                        'mdt_id'       => $enemy->mdt_id,
                        'enemy_id'     => $enemy->id,
                    ];
                    $validEnemyIds[] = (int)$enemyId;
                }

                // Bulk insert
                KillZoneEnemy::insert($killZoneEnemies);

                // Reload the relation so getEnemiesAttribute() returns accurate data
                $killZone->load('enemies');
            }

            // May be null for mass request
            if ($spellIds !== null) {
                $killZone->killZoneSpells()->delete();

                $spellsAttributes = [];
                foreach ($spellIds as $spellId) {
                    $spellsAttributes[] = [
                        'kill_zone_id' => $killZone->id,
                        'spell_id'     => $spellId,
                    ];
                }

                KillZoneSpell::insert($spellsAttributes);
                $killZone->load(['spells:id,icon_name']);
            }

            if ($recalculateEnemyForces) {
                // afterCommit: the caller wraps this in a transaction, and a queue worker that picks
                // the job up before it commits would recalculate the enemy forces from pre-commit
                // state - or from state that a retried attempt rolled back entirely (#4260)
                RefreshEnemyForces::dispatch($dungeonroute->id)->afterCommit();
            }

            $this->dungeonRouteChanged($dungeonroute, $beforeModel, $killZone, function (
                array & $beforeAttributes,
                array & $afterAttributes,
            ) use ($beforeEnemyIds, $killZone) {
                $beforeAttributes['enemies'] = $beforeEnemyIds;
                $afterAttributes['enemies']  = $killZone->enemies->pluck('id');
            });

            // The row now holds the real-floor location; echo the location back in the plane the
            // client submitted it in, so the marker it just placed does not jump on its map
            if ($submittedFacadeLatLng !== null) {
                $killZone->setAttribute('lat', $submittedFacadeLatLng->getLat());
                $killZone->setAttribute('lng', $submittedFacadeLatLng->getLng());
                $killZone->setAttribute('floor_id', $submittedFacadeLatLng->getFloor()->id);
                $killZone->setRelation('floor', $submittedFacadeLatLng->getFloor());
            }

            if (Auth::check()) {
                // Something's updated; broadcast it
                /** @var User $user */
                $user = Auth::user();

                // afterCommit: collaborators must not be told about a pull that a rollback (or a
                // retried attempt) then took away again - they would render enemies that no longer
                // belong to it (#4260). Runs immediately when no transaction is open.
                DB::afterCommit(static function () use ($coordinatesService, $dungeonroute, $user, $killZone): void {
                    try {
                        broadcast(new KillZoneChangedEvent($coordinatesService, $dungeonroute, $user, $killZone));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                });
            }
        } else {
            throw new Exception('Unable to save kill zone!');
        }

        return $killZone;
    }

    /**
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function store(
        CoordinatesServiceInterface  $coordinatesService,
        KillZonePathServiceInterface $killZonePathService,
        APIKillZoneFormRequest       $request,
        DungeonRoute                 $dungeonRoute,
        ?KillZone                    $killZone = null,
    ): KillZone|Response {
        // Outside the try/catch on purpose: an authorization failure must surface as a 403, not be
        // rewritten into the 404 below.
        $dungeonRoute = $this->authorizeKillZoneEdit($dungeonRoute, $killZone);

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
            $result = DB::transaction(fn(): KillZone => $this->saveKillZone(
                $coordinatesService,
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
        CoordinatesServiceInterface  $coordinatesService,
        KillZonePathServiceInterface $killZonePathService,
        APIKillZoneMassFormRequest   $request,
        DungeonRoute                 $dungeonRoute,
    ) {
        // Fast-fail on the URL's own route before parsing the batch; each item below is then
        // re-authorized against its own real route, since a batch entry's id could belong to a
        // different route than this one.
        Gate::authorize('edit', $dungeonRoute);

        $validated = $request->validated();

        // Set by the batch loop below so the response can name the pull that could not be saved,
        // even though the failure now has to travel out of the transaction as an exception
        $notFoundKillZoneId = null;

        try {
            // One transaction for the whole batch, not one per pull: the bulk enemy delete at the
            // bottom spans every pull in the request, so a failure between it and the matching
            // insert used to permanently empty all of them at once (#4260). Retried because
            // `kill_zone_enemies` is one of the most contended tables in the app (#4239).
            $enemyForces = DB::transaction(function () use (
                $coordinatesService,
                $dungeonRoute,
                $validated,
                &$notFoundKillZoneId,
            ): int {
                // Update killzones
                $killZones = new Collection();
                foreach ($validated['killzones'] ?? [] as $killZoneData) {
                    try {
                        /** @var KillZone|null $existingKillZone */
                        $existingKillZone = isset($killZoneData['id'])
                            ? KillZone::with('dungeonRoute')->find($killZoneData['id'])
                            : null;
                        $killZoneDungeonRoute = $this->authorizeKillZoneEdit($dungeonRoute, $existingKillZone);

                        // Unset the enemies since we're quicker to update that in bulk here
                        $kzDataWithoutEnemies = $killZoneData;
                        unset($kzDataWithoutEnemies['enemies']);
                        // Do not save the enemy forces - we save it one time down below
                        $killZones->push(
                            $this->saveKillZone(
                                $coordinatesService,
                                $killZoneDungeonRoute,
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

                // Insert new enemies based on what was sent
                foreach ($validated['killzones'] ?? [] as $killZoneData) {
                    try {
                        if (isset($killZoneData['enemies'])) {
                            // Filter enemies - only allow those who are actually on the allowed floors (don't couple to enemies in other dungeons)
                            $killZoneDataEnemies = array_filter($killZoneData['enemies'], static fn(
                                $item,
                            ) => in_array($item, $validEnemyIds));

                            // Assign kill zone to each passed enemy
                            foreach ($killZoneDataEnemies as $killZoneDataEnemyId) {
                                $enemy             = $enemies->get($killZoneDataEnemyId);
                                $killZoneEnemies[] = [
                                    'kill_zone_id' => $killZoneData['id'],
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
            'killzone_paths' => $this->getKillZonePaths($killZonePathService, $dungeonRoute),
        ];
    }

    /**
     * @return array<string, mixed>|ResponseFactory|Response
     *
     * @throws \Exception
     */
    public function delete(
        KillZonePathServiceInterface $killZonePathService,
        Request                      $request,
        DungeonRoute                 $dungeonRoute,
        KillZone                     $killZone,
    ) {
        $dungeonRoute = $killZone->dungeonRoute;

        if (!$dungeonRoute->isSandbox()) {
            // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's map comment
            Gate::authorize('edit', $dungeonRoute);
        }

        // KillZone::deleting cascades into kill_zone_enemies and kill_zone_spells, and the route's
        // enemy_forces and change log follow - six writes that a failure part-way through used to
        // leave half-applied (#4260). Distinguishes a refused delete from a failed one so the
        // pre-existing 500 vs 404 split survives the exception having to escape the transaction.
        $deleteRefused = false;

        try {
            $enemyForces = DB::transaction(function () use ($killZone, $dungeonRoute, &$deleteRefused): int {
                // Re-read per attempt: Model::delete() flips `exists` to false and that survives a
                // rollback, so a retried attempt would find the instance already "deleted", return
                // null and delete nothing while every other write in here re-ran (#4250)
                /** @var KillZone|null $freshKillZone */
                $freshKillZone = KillZone::find($killZone->id);

                if ($freshKillZone === null) {
                    throw new Exception('Kill zone no longer exists');
                }

                if (!$freshKillZone->delete()) {
                    $deleteRefused = true;

                    throw new Exception('Unable to delete pull');
                }

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::user();

                    // afterCommit: collaborators must not be told the pull is gone while it can
                    // still come back through a rollback or a retried attempt
                    DB::afterCommit(static function () use ($dungeonRoute, $user, $freshKillZone): void {
                        try {
                            broadcast(new KillZoneDeletedEvent($dungeonRoute, $user, $freshKillZone));
                        } catch (BroadcastException) {
                            // Ignore broadcast failures
                        }
                    });
                }

                $dungeonRoute->load('killZones');
                $enemyForces = $dungeonRoute->getEnemyForces();

                // Query builder, timestamp included rather than touch(): $dungeonRoute outlives
                // this closure, so on a retry Eloquent would find it clean and write nothing (#4250)
                DungeonRoute::query()->whereKey($dungeonRoute->id)->update([
                    'enemy_forces' => $enemyForces,
                    'updated_at'   => Carbon::now(),
                ]);
                $dungeonRoute->setAttribute('enemy_forces', $enemyForces);

                $this->dungeonRouteChanged($dungeonRoute, $freshKillZone, null);

                return $enemyForces;
            }, 3);
        } catch (Exception $exception) {
            report($exception);

            return $deleteRefused
                ? response(__('controller.killzone.error.unable_to_delete_pull'), Http::INTERNAL_SERVER_ERROR)
                : response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
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
                // Clearing a route is six writes per pull plus the prideful enemies and the route
                // itself; without a transaction a failure part-way through left the route
                // half-cleared, with no way for the client to tell how far it got (#4260)
                DB::transaction(function () use ($dungeonRoute): void {
                    // Queried rather than read off $dungeonRoute's relation: Model::delete() flips
                    // `exists` to false and that survives a rollback, so a retried attempt would
                    // iterate the same already-"deleted" instances, delete nothing, and still report
                    // the route as cleared (#4250)
                    $killZones = KillZone::query()
                        ->where('dungeon_route_id', $dungeonRoute->id)
                        ->get();
                    $pridefulEnemies = PridefulEnemy::query()
                        ->where('dungeon_route_id', $dungeonRoute->id)
                        ->get();

                    // Deleted one at a time - a mass delete on the relation skips KillZone::deleting, which
                    // is what cleans up the kill zone's enemies and spells
                    foreach ($killZones as $killZone) {
                        $killZone->delete();
                    }
                    $dungeonRoute->pridefulEnemies()->delete();

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::user();
                        foreach ($killZones as $killZone) {
                            $this->dungeonRouteChanged($dungeonRoute, $killZone, null);
                        }

                        // afterCommit: collaborators must not be told the route was cleared while a
                        // rollback or a retried attempt can still put every pull back
                        DB::afterCommit(static function () use ($dungeonRoute, $user, $killZones, $pridefulEnemies): void {
                            foreach ($killZones as $killZone) {
                                try {
                                    broadcast(new KillZoneDeletedEvent($dungeonRoute, $user, $killZone));
                                } catch (BroadcastException) {
                                    // Ignore broadcast failures
                                }
                            }

                            foreach ($pridefulEnemies as $pridefulEnemy) {
                                try {
                                    broadcast(new PridefulEnemyDeletedEvent($dungeonRoute, $user, $pridefulEnemy));
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
