<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiff;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffEnemy;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffMovedEnemy;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffNpcEnemyForces;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffPull;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;

readonly class MappingVersionUpgradeDiffService implements MappingVersionUpgradeDiffServiceInterface
{
    public function __construct(
        private CoordinatesServiceInterface  $coordinatesService,
        private DungeonRouteServiceInterface $dungeonRouteService,
    ) {
    }

    #[Override]
    public function diffForUpgradeDraft(DungeonRoute $draft): ?MappingVersionUpgradeDiff
    {
        $original = $draft->upgradeOfDungeonRoute;

        // Deleting an original deletes its draft, but a request that resolved both before that still gets here
        if ($original === null || $original->mappingVersion === null || $draft->mappingVersion === null) {
            return null;
        }

        return $this->diff($original, $draft->mappingVersion, $draft);
    }

    #[Override]
    public function diff(
        DungeonRoute   $original,
        MappingVersion $newMappingVersion,
        ?DungeonRoute  $upgradedDungeonRoute = null,
    ): MappingVersionUpgradeDiff {
        $oldMappingVersion = $original->mappingVersion;
        $teeming           = (bool)$original->teeming;

        $oldEnemies = $this->loadEnemies($oldMappingVersion);
        $newEnemies = $this->loadEnemies($newMappingVersion);

        $oldEnemiesById      = $oldEnemies->keyBy('id');
        $newEnemiesByPullKey = $newEnemies
            ->filter(static fn(Enemy $enemy): bool => MappingVersionUpgradeMatchKey::forEnemyAsPullEnemy($enemy) !== null)
            ->groupBy(static fn(Enemy $enemy): string => MappingVersionUpgradeMatchKey::forEnemyAsPullEnemy($enemy));
        $newEnemiesByMarkerKey = $newEnemies
            ->filter(static fn(Enemy $enemy): bool => MappingVersionUpgradeMatchKey::forEnemyAsRaidMarker($enemy) !== null)
            ->groupBy(static fn(Enemy $enemy): string => MappingVersionUpgradeMatchKey::forEnemyAsRaidMarker($enemy));

        $original->loadMissing(['killZones.killZoneEnemies.npc', 'enemyRaidMarkers']);

        /** @var Collection<int, MappingVersionUpgradeDiffEnemy> $removedPullEnemies */
        $removedPullEnemies = collect();
        /** @var Collection<int, MappingVersionUpgradeDiffPull> $emptiedPulls */
        $emptiedPulls = collect();
        /** @var Collection<int, MappingVersionUpgradeDiffMovedEnemy> $movedPullEnemies */
        $movedPullEnemies = collect();

        /** @var array<int, bool> $survivingEnemyIds ids in the new mapping version, used as a set */
        $survivingEnemyIds = [];
        /**
         * The NPC ids the enemy forces query awards forces for - kill_zone_enemies.npc_id, which is MDT's NPC id.
         *
         * @var array<int, bool>
         */
        $pulledNpcIds = [];

        foreach ($original->killZones as $killZone) {
            $killZoneEnemies = $killZone->killZoneEnemies;
            if ($killZoneEnemies->isEmpty()) {
                continue;
            }

            $pull          = new MappingVersionUpgradeDiffPull($killZone->index, $killZone->color);
            $survivorCount = 0;

            foreach ($killZoneEnemies as $killZoneEnemy) {
                if ($killZoneEnemy->npc_id !== null) {
                    $pulledNpcIds[$killZoneEnemy->npc_id] = true;
                }

                $oldEnemy = $killZoneEnemy->enemy_id === null ? null : $oldEnemiesById->get($killZoneEnemy->enemy_id);
                $newEnemy = $this->findNewEnemyFor($killZoneEnemy, $newEnemiesByPullKey);

                if ($newEnemy === null) {
                    $removedPullEnemies->push(new MappingVersionUpgradeDiffEnemy(
                        $pull,
                        $killZoneEnemy->npc_id,
                        $killZoneEnemy->npc ?? $oldEnemy?->npc,
                        $oldEnemy?->id,
                        $oldEnemy?->floor,
                        $oldEnemy?->lat,
                        $oldEnemy?->lng,
                    ));

                    continue;
                }

                $survivorCount++;
                $survivingEnemyIds[$newEnemy->id] = true;

                $movedPullEnemy = $this->resolveMovedPullEnemy($pull, $oldEnemy, $newEnemy);
                if ($movedPullEnemy !== null) {
                    $movedPullEnemies->push($movedPullEnemy);
                }
            }

            if ($survivorCount === 0) {
                $emptiedPulls->push($pull);
            }
        }

        $killedEnemyIds = $upgradedDungeonRoute === null
            ? $survivingEnemyIds
            : $this->getKilledEnemyIds($upgradedDungeonRoute);

        return new MappingVersionUpgradeDiff(
            oldMappingVersion: $oldMappingVersion,
            newMappingVersion: $newMappingVersion,
            removedPullEnemies: $removedPullEnemies,
            emptiedPulls: $emptiedPulls,
            movedPullEnemies: $movedPullEnemies,
            unkilledRequiredEnemies: $this->resolveUnkilledRequiredEnemies($oldEnemies, $newEnemies, $killedEnemyIds, $teeming),
            npcEnemyForcesChanges: $this->resolveNpcEnemyForcesChanges(
                $oldMappingVersion,
                $newMappingVersion,
                array_keys($pulledNpcIds),
                $teeming,
            ),
            oldEnemyForces: $original->enemy_forces,
            newEnemyForces: $upgradedDungeonRoute?->enemy_forces,
            oldEnemyForcesRequired: $this->enemyForcesRequiredOf($oldMappingVersion, $teeming),
            newEnemyForcesRequired: $this->enemyForcesRequiredOf($newMappingVersion, $teeming),
            lostRaidMarkerCount: $this->countLostRaidMarkers($original, $newEnemiesByMarkerKey),
            dungeonStartLost: $original->dungeon_start_map_icon_id !== null
                && $this->dungeonRouteService->findDungeonStartMapIconIdForMappingVersion($original, $newMappingVersion->id) === null,
            addedEnemyCount: $this->countEnemiesOnlyIn($newEnemies, $oldEnemies),
            removedEnemyCount: $this->countEnemiesOnlyIn($oldEnemies, $newEnemies),
            oldEnemyPatrolCount: EnemyPatrol::query()->where('mapping_version_id', $oldMappingVersion->id)->count(),
            newEnemyPatrolCount: EnemyPatrol::query()->where('mapping_version_id', $newMappingVersion->id)->count(),
        );
    }

    /**
     * @return EloquentCollection<int, Enemy>
     */
    private function loadEnemies(MappingVersion $mappingVersion): EloquentCollection
    {
        return Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->with(['npc', 'floor'])
            ->get();
    }

    /**
     * The enemy in the new mapping version the upgrade re-resolved this pull enemy onto, or null when it dropped it.
     *
     * When the identity resolves to more than one enemy the upgrade's UPDATE ... JOIN lets MySQL pick one, so the
     * pull keeps an enemy either way - which one it is cannot be predicted here, and does not change the diff.
     *
     * @param Collection<string, EloquentCollection<int, Enemy>> $newEnemiesByPullKey
     */
    private function findNewEnemyFor(KillZoneEnemy $killZoneEnemy, Collection $newEnemiesByPullKey): ?Enemy
    {
        $matchKey = MappingVersionUpgradeMatchKey::forPullEnemy($killZoneEnemy->npc_id, $killZoneEnemy->mdt_id);

        return $matchKey === null ? null : $newEnemiesByPullKey->get($matchKey)?->first();
    }

    /**
     * Whether the new mapping version places a kept pull enemy somewhere the author would notice.
     */
    private function resolveMovedPullEnemy(
        MappingVersionUpgradeDiffPull $pull,
        ?Enemy                        $oldEnemy,
        Enemy                         $newEnemy,
    ): ?MappingVersionUpgradeDiffMovedEnemy {
        // Without the enemy the pull pointed at in the old mapping version there is nothing to measure against
        if ($oldEnemy === null) {
            return null;
        }

        $makeMovedEnemy = static fn(?float $distance): MappingVersionUpgradeDiffMovedEnemy => new MappingVersionUpgradeDiffMovedEnemy(
            $pull,
            $newEnemy->mdt_npc_id ?? $newEnemy->npc_id,
            $newEnemy->npc,
            $newEnemy->id,
            $oldEnemy->floor,
            $newEnemy->floor,
            $newEnemy->lat,
            $newEnemy->lng,
            $distance,
        );

        if ($oldEnemy->floor_id !== $newEnemy->floor_id) {
            return $makeMovedEnemy(null);
        }

        if (!$oldEnemy->hasValidLatLng() || !$newEnemy->hasValidLatLng()) {
            return null;
        }

        try {
            $distance = $this->coordinatesService->distanceIngameXY(
                $this->coordinatesService->calculateIngameLocationForMapLocation($oldEnemy->getLatLng()),
                $this->coordinatesService->calculateIngameLocationForMapLocation($newEnemy->getLatLng()),
            );
        } catch (InvalidArgumentException) {
            // A facade floor or a floor without ingame coordinates cannot be measured in yards. Nothing is more
            // honest to say about the enemy than nothing at all - lat/lng is not comparable across floors.
            return null;
        }

        if ($distance < (float)config('keystoneguru.mapping_version_upgrade_diff.enemy_moved_min_distance_yd')) {
            return null;
        }

        return $makeMovedEnemy($distance);
    }

    /**
     * The required enemies of the new mapping version the route does not kill - what Apply refuses a published
     * original over, newly required ones first.
     *
     * @param  EloquentCollection<int, Enemy>                  $oldEnemies
     * @param  EloquentCollection<int, Enemy>                  $newEnemies
     * @param  array<int, bool>                                $killedEnemyIds
     * @return Collection<int, MappingVersionUpgradeDiffEnemy>
     */
    private function resolveUnkilledRequiredEnemies(
        EloquentCollection $oldEnemies,
        EloquentCollection $newEnemies,
        array              $killedEnemyIds,
        bool               $teeming,
    ): Collection {
        $oldRequiredIdentities = $oldEnemies
            ->filter(fn(Enemy $enemy): bool => $enemy->required && $this->isEnemyVisibleForTeeming($enemy, $teeming))
            ->mapWithKeys(fn(Enemy $enemy): array => [$this->enemyIdentityOf($enemy) => true])
            ->all();

        return $newEnemies
            ->filter(fn(Enemy $enemy): bool => $enemy->required
                && $this->isEnemyVisibleForTeeming($enemy, $teeming)
                && !isset($killedEnemyIds[$enemy->id]))
            ->map(fn(Enemy $enemy): MappingVersionUpgradeDiffEnemy => new MappingVersionUpgradeDiffEnemy(
                null,
                $enemy->mdt_npc_id ?? $enemy->npc_id,
                $enemy->npc,
                $enemy->id,
                $enemy->floor,
                $enemy->lat,
                $enemy->lng,
                !isset($oldRequiredIdentities[$this->enemyIdentityOf($enemy)]),
            ))
            ->sortByDesc(static fn(MappingVersionUpgradeDiffEnemy $enemy): bool => $enemy->newlyRequired)
            ->values();
    }

    /**
     * The enemy forces the mapping now awards for the NPCs this route pulls, where the two versions disagree.
     *
     * Per-enemy overrides and the Shrouded affix amounts are deliberately left out: those live on the enemy and on
     * the mapping version rather than per NPC, and the exact totals either side of the upgrade are already reported.
     *
     * @param  array<int, int>                                          $npcIds
     * @return Collection<int, MappingVersionUpgradeDiffNpcEnemyForces>
     */
    private function resolveNpcEnemyForcesChanges(
        MappingVersion $oldMappingVersion,
        MappingVersion $newMappingVersion,
        array          $npcIds,
        bool           $teeming,
    ): Collection {
        /** @var Collection<int, MappingVersionUpgradeDiffNpcEnemyForces> $changes */
        $changes = collect();

        if ($npcIds === []) {
            return $changes;
        }

        $oldNpcEnemyForces = $this->loadNpcEnemyForces($oldMappingVersion, $npcIds);
        $newNpcEnemyForces = $this->loadNpcEnemyForces($newMappingVersion, $npcIds);

        foreach ($npcIds as $npcId) {
            $old = $oldNpcEnemyForces->get($npcId);
            $new = $newNpcEnemyForces->get($npcId);

            $oldEnemyForces = $this->enemyForcesOf($old, $teeming);
            $newEnemyForces = $this->enemyForcesOf($new, $teeming);

            if ($oldEnemyForces === $newEnemyForces) {
                continue;
            }

            $changes->push(new MappingVersionUpgradeDiffNpcEnemyForces(
                $npcId,
                ($new ?? $old)?->npc,
                $oldEnemyForces,
                $newEnemyForces,
            ));
        }

        return $changes;
    }

    /**
     * @param  array<int, int>                 $npcIds
     * @return Collection<int, NpcEnemyForces>
     */
    private function loadNpcEnemyForces(MappingVersion $mappingVersion, array $npcIds): Collection
    {
        return NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('npc_id', $npcIds)
            ->with('npc')
            ->get()
            ->keyBy('npc_id');
    }

    private function enemyForcesOf(?NpcEnemyForces $npcEnemyForces, bool $teeming): ?int
    {
        if ($npcEnemyForces === null) {
            return null;
        }

        return $teeming
            ? $npcEnemyForces->enemy_forces_teeming ?? $npcEnemyForces->enemy_forces
            : $npcEnemyForces->enemy_forces;
    }

    private function enemyForcesRequiredOf(MappingVersion $mappingVersion, bool $teeming): int
    {
        return $teeming ? $mappingVersion->enemy_forces_required_teeming : $mappingVersion->enemy_forces_required;
    }

    /**
     * @param Collection<string, EloquentCollection<int, Enemy>> $newEnemiesByMarkerKey
     */
    private function countLostRaidMarkers(DungeonRoute $original, Collection $newEnemiesByMarkerKey): int
    {
        return $original->enemyRaidMarkers
            ->filter(static function (DungeonRouteEnemyRaidMarker $raidMarker) use ($newEnemiesByMarkerKey): bool {
                $matchKey = MappingVersionUpgradeMatchKey::forRaidMarker($raidMarker->npc_id, $raidMarker->mdt_id);
                if ($matchKey === null) {
                    return true;
                }

                // The upgrade refuses to guess when one identity resolves to several enemies, and drops the marker
                return $newEnemiesByMarkerKey->get($matchKey)?->count() !== 1;
            })
            ->count();
    }

    /**
     * How many enemies $subject holds that $other does not, counting a duplicated identity as many times as it
     * is duplicated.
     *
     * @param EloquentCollection<int, Enemy> $subject
     * @param EloquentCollection<int, Enemy> $other
     */
    private function countEnemiesOnlyIn(EloquentCollection $subject, EloquentCollection $other): int
    {
        $otherCounts = $other->groupBy(fn(Enemy $enemy): string => $this->enemyIdentityOf($enemy))->map->count();

        return $subject
            ->groupBy(fn(Enemy $enemy): string => $this->enemyIdentityOf($enemy))
            ->map->count()
            ->map(static fn(int $count, string $identity): int => max(0, $count - ($otherCounts->get($identity) ?? 0)))
            ->sum();
    }

    /**
     * How an enemy is recognised as "the same enemy" across two mapping versions. Unlike the upgrade's own match
     * key this never returns null, so enemies MDT never placed are still compared against each other.
     */
    private function enemyIdentityOf(Enemy $enemy): string
    {
        return sprintf('%s-%s', $enemy->mdt_npc_id ?? $enemy->npc_id ?? 'null', $enemy->mdt_id ?? 'null');
    }

    /**
     * @see DungeonRoute::hasKilledAllRequiredEnemies()
     */
    private function isEnemyVisibleForTeeming(Enemy $enemy, bool $teeming): bool
    {
        return match ($enemy->teeming) {
            Enemy::TEEMING_VISIBLE => $teeming,
            Enemy::TEEMING_HIDDEN  => !$teeming,
            default                => true,
        };
    }

    /**
     * @return array<int, bool>
     */
    private function getKilledEnemyIds(DungeonRoute $dungeonRoute): array
    {
        $dungeonRoute->loadMissing('killZones.enemies');

        $result = [];

        foreach ($dungeonRoute->killZones as $killZone) {
            foreach ($killZone->enemies as $enemy) {
                $result[$enemy->id] = true;
            }
        }

        return $result;
    }
}
