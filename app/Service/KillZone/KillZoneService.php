<?php

namespace App\Service\KillZone;

use App\Events\Models\KillZone\KillZoneChangedEvent;
use App\Events\Models\KillZone\KillZoneDeletedEvent;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Jobs\RefreshEnemyForces;
use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZone\Exceptions\KillZoneDeleteRefusedException;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KillZoneService implements KillZoneServiceInterface
{
    use ChangesDungeonRoute;
    use EnforcesDungeonRouteLimits;

    /**
     * The renumber_leaked_enemy_ids migration moved enemies 999999106-999999529 down by this offset. A map editor
     * loaded before that deploy still submits the old ids, and the pull would lose those enemies on save.
     */
    private const int RENUMBERED_ENEMY_ID_MIN = 999999106;

    private const int RENUMBERED_ENEMY_ID_MAX = 999999529;

    private const int RENUMBERED_ENEMY_ID_OFFSET = 999847826;

    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * Rewrites a submitted kill zone location that is expressed on a facade (combined multi-floor)
     * map onto the real floor it belongs to, mutating $data so the conversion happens *before* the
     * row is written rather than as a second update afterwards.
     *
     * Whether the submitted location is a facade location is derived from the submitted floor's own
     * `facade` flag, deliberately not from the acting user's map facade style: that style is
     * request-scoped state which the save request does not necessarily share with the request that
     * rendered the map the location was placed on, and a mismatch would persist the facade floor
     * verbatim.
     *
     * @throws HttpException when a facade location on a facade-rendering route belongs to no real floor
     *
     * @param  array<string, mixed> $data
     * @return LatLng|null          the location exactly as submitted, so the response can echo it back in the
     *                              plane the client is rendering, or null if no facade location was
     *                              submitted or the submitted one is dropped
     */
    private function convertSubmittedFacadeLocation(
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

        $convertedLatLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
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
            // by an earlier bug, and refusing it would make that pull uneditable forever. Drop it and
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
     * AjaxKillZoneController::storeAll()'s batch is atomic as a whole rather than per pull, and so that a retry after a
     * deadlock re-runs the entire unit instead of a savepoint MySQL has already discarded. Every
     * model it touches is (re)hydrated inside this method, so a retried attempt starts clean.
     *
     * @throws \Exception
     *
     * @param array<string, mixed> $data
     */
    public function saveKillZone(
        DungeonRoute $dungeonroute,
        array        $data,
        bool         $recalculateEnemyForces = true,
    ): KillZone {
        $enemyIds = $data['enemies'] ?? null;
        unset($data['enemies']);
        if ($enemyIds !== null) {
            $enemyIds = array_map(self::translateRenumberedEnemyId(...), $enemyIds);
        }
        $data['dungeon_route_id'] = $dungeonroute->id;

        $spellIds = $data['spells'] ?? null;

        /** @var KillZone $killZone */
        $killZone = KillZone::with('dungeonRoute')->findOrNew($data['id'] ?? null);

        $this->abortIfDungeonRouteLimitReached($dungeonroute, DungeonRouteLimitType::KillZones);

        // Must happen before the row is written - the database may never hold a facade floor
        $submittedFacadeLatLng = $this->convertSubmittedFacadeLocation($dungeonroute, $killZone, $data);

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
                // state - or from state that a retried attempt rolled back entirely
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
                // retried attempt) then took away again - they would render enemies that are not
                // part of it. Runs immediately when no transaction is open.
                $coordinatesService = $this->coordinatesService;
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

    public function deleteKillZone(DungeonRoute $dungeonRoute, KillZone $killZone): int
    {
        // KillZone::deleting cascades into kill_zone_enemies and kill_zone_spells, and the route's enemy_forces and
        // change log follow - six writes that must land together or not at all
        return DB::transaction(function () use ($killZone, $dungeonRoute): int {
            // Re-read per attempt: Model::delete() flips `exists` to false and that survives a
            // rollback, so a retried attempt would find the instance already "deleted", return
            // null and delete nothing while every other write in here re-ran
            /** @var KillZone|null $freshKillZone */
            $freshKillZone = KillZone::find($killZone->id);

            if ($freshKillZone === null) {
                throw new Exception('Kill zone no longer exists');
            }

            if (!$freshKillZone->delete()) {
                throw new KillZoneDeleteRefusedException('Unable to delete pull');
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
            // this closure, so on a retry Eloquent would find it clean and write nothing
            DungeonRoute::query()->whereKey($dungeonRoute->id)->update([
                'enemy_forces' => $enemyForces,
                'updated_at'   => Carbon::now(),
            ]);
            $dungeonRoute->setAttribute('enemy_forces', $enemyForces);

            $this->dungeonRouteChanged($dungeonRoute, $freshKillZone, null);

            return $enemyForces;
        }, 3);
    }

    private static function translateRenumberedEnemyId(int|string $enemyId): int
    {
        $enemyId = (int)$enemyId;

        return $enemyId >= self::RENUMBERED_ENEMY_ID_MIN && $enemyId <= self::RENUMBERED_ENEMY_ID_MAX
            ? $enemyId - self::RENUMBERED_ENEMY_ID_OFFSET
            : $enemyId;
    }
}
