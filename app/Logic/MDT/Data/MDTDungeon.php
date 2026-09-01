<?php

/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 06/01/2019
 * Time: 18:10
 */

namespace App\Logic\MDT\Data;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTNpc;
use App\Logic\MDT\Exception\FacadeNotConfiguredException;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTExpansionException;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Lua;

/**
 * @author Wouter
 *
 * @since 05/01/2019
 */
class MDTDungeon
{
    public function __construct(
        private readonly CacheServiceInterface       $cacheService,
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly Dungeon                     $dungeon,
    ) {
        if (!Conversion::hasMDTDungeonName($this->dungeon->key)) {
            throw new InvalidMDTDungeonException(sprintf('Unsupported MDT dungeon for dungeon key %s!', $this->dungeon->key));
        }

        if (!Conversion::getMDTExpansionName($this->dungeon->key)) {
            throw new InvalidMDTExpansionException(sprintf('Unsupported MDT expansion for dungeon key %s!', $this->dungeon->key));
        }
    }

    /**
     * @return array{normal: int, teeming: int, teemingEnabled: bool}
     *
     * @throws Exception
     */
    public function getDungeonTotalCount(): array
    {
        $lua = $this->getLua();
        /** @phpstan-ignore argument.type (Lua C extension uses string-based function name calling) */
        $dungeonTotalCount = $lua->call('GetDungeonTotalCount');

        return [
            'normal'         => (int)$dungeonTotalCount['normal'],
            'teeming'        => (int)($dungeonTotalCount['teeming'] ?? 0),
            'teemingEnabled' => $dungeonTotalCount['teemingEnabled'] ?? false,
        ];
    }

    /**
     * @throws Exception
     */
    public function getMDTDungeonID(): int
    {
        $lua = $this->getLua();

        /** @phpstan-ignore argument.type (Lua C extension uses string-based function name calling) */
        return $lua->call('GetDungeonIndex');
    }

    /**
     * Get a list of NPCs
     *
     * @return Collection<int, MDTNpc>
     *
     * @throws Exception
     */
    public function getMDTNPCs(): Collection
    {
        return $this->cacheService->remember(sprintf('mdt_npcs_%s', $this->dungeon->key), function () {
            $mdtNpcs = new Collection();

            $lua = $this->getLua();
            /** @phpstan-ignore argument.type (Lua C extension uses string-based function name calling) */
            $rawMdtEnemies = $lua->call('GetDungeonEnemies');

            foreach ($rawMdtEnemies as $mdtNpcIndex => $mdtNpc) {
                $mdtNpcs->push(new MDTNpc((int)$mdtNpcIndex, $mdtNpc));
            }

            return $mdtNpcs;
        }, config('keystoneguru.cache.mdt.ttl'));
    }

    /**
     * @return Collection<int, MDTMapPOI>
     *
     * @throws Exception
     */
    public function getMDTMapPOIs(): Collection
    {
        $lua = $this->getLua();
        /** @phpstan-ignore argument.type (Lua C extension uses string-based function name calling) */
        $rawMdtMapPOIs = $lua->call('GetMapPOIs');
        $result        = new Collection();

        // May be null
        foreach ($rawMdtMapPOIs ?? [] as $subLevel => $pois) {
            foreach ($pois as $poiIndex => $poi) {
                $result->push(new MDTMapPOI((int)$subLevel, $poi));
            }
        }

        return $result;
    }

    /**
     * Get all clones of this dungeon in the format of enemies (Keystone.guru style).
     *
     * @param  EloquentCollection<int, Floor> $floors The floors that you want to get the clones for.
     * @return Collection<int, Enemy>
     */
    public function getClonesAsEnemies(MappingVersion $mappingVersion, Collection $floors): Collection
    {
        return $this->cacheService->remember(sprintf('mdt_enemies_%s_%d', $this->dungeon->key, $mappingVersion->id), function () use (
            $mappingVersion,
            $floors
        ) {
            $enemies = new Collection();

            try {
                $mdtNpcs = $this->getMDTNPCs();
            } catch (Exception $exception) {
                logger()->error($exception->getMessage());

                return $enemies;
            }

            // Ensure floors is a collection
            if (!($floors instanceof Collection)) { // @phpstan-ignore instanceof.alwaysTrue
                $floors = [$floors];
            }

            // A bit of a hack, but it works. If we have a floor with a facade in it, we only parse THAT floor
            // since that's the only floor that MDT will have. We will then put the enemies in the correct floors.
            // Pinky promise.
            // Facade coordinate redistribution (CoordinatesService::convertFacadeMapLocationToMapLocation())
            // only works when facade_enabled is actually true, so only take the facade-only path then - Throne
            // of the Tides has a facade floor it has never switched on (#3742), and so does every historical
            // mapping version predating its dungeon's facade.
            if ($mappingVersion->facade_enabled) {
                $facadeFloors = $floors->filter(static fn(Floor $floor) => $floor->facade);

                // facade_enabled promises a usable facade floor plus floor unions to redistribute its
                // coordinates through - if either is missing, the mapping version's facade setup is
                // incomplete. Fail loudly instead of guessing: fix the setup and rerun.
                if ($facadeFloors->isEmpty() || $mappingVersion->floorUnions()->whereIn('floor_id', $facadeFloors->pluck('id'))->doesntExist()) {
                    throw new FacadeNotConfiguredException(sprintf(
                        'Mapping version %d of dungeon %s has facade_enabled=true, but no usable facade was found '
                        . '(no facade floor among the given floors, and/or no floor unions on its facade floor). '
                        . 'Fix the facade setup on this mapping version and retry.',
                        $mappingVersion->id,
                        $this->dungeon->key,
                    ));
                }

                $floors = $facadeFloors;
            } else {
                // Without a facade the enemies must land on real floors - a facade floor's coordinates cannot
                // be converted back (CoordinatesService::calculateIngameLocationForMapLocation throws on them).
                $floors = $floors->filter(static fn(Floor $floor) => !$floor->facade);

                if ($floors->isEmpty()) {
                    // Returning an empty collection here would silently import zero enemies and report success,
                    // so say so loudly instead - see #3737 for why a quiet no-op is the worst outcome.
                    logger()->error(sprintf(
                        'No usable non-facade floors left for dungeon %s - cannot place any MDT enemies.',
                        $this->dungeon->key,
                    ));

                    return $enemies;
                }
            }

            $floors->load(['dungeon']);
            /** @var Collection<int, Floor> $floors */
            $floors = $floors->keyBy('id');

            // NPC_ID => list of clones
            $npcClones = [];
            // Find the enemy in a list of enemies
            foreach ($mdtNpcs as $mdtNpc) {
                $cloneCount = 0;
                foreach ($mdtNpc->getClones() as $mdtCloneIndex => $clone) {
                    //Only clones that are on the same floor
                    foreach ($floors as $floor) {
                        if ((int)$clone['sublevel'] === ($floor->mdt_sub_level ?? $floor->index)) {
                            // Set some additional props that come in handy when converting to an enemy
                            $clone['mdtNpcIndex'] = $mdtNpc->getIndex();
                            // Group ID
                            $clone['g'] ??= -1;

                            $npcId = $mdtNpc->getId();
                            // Make sure array is set
                            if (!isset($npcClones[$npcId])) {
                                $npcClones[$npcId] = [];
                            }

                            // Place the enemy on the correct floor
                            $latLng = Conversion::convertMDTCoordinateToLatLng($clone, $floor);
                            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng);

                            $clone = array_merge($clone, $latLng->toArray());

                            // Gets funky here. There's instances where MDT has defined an NPC with the same NPC_ID twice
                            // This fucks with the assignment below this if, because it'll overwrite the NPCs there.
                            // We don't want this; instead append it at the end of the current array at the proper index
                            // We calculate that at the hand of the current index in the second array ($cloneCount).
                            if (isset($npcClones[$npcId][$latLng->getFloor()->id][$mdtCloneIndex])) {
                                $mdtCloneIndex += (count($npcClones[$npcId][$latLng->getFloor()->id]) - $cloneCount);
                            }

                            // Append this clone to the array
                            $npcClones[$npcId][$latLng->getFloor()->id][$mdtCloneIndex] = $clone;
                        }
                    }

                    $cloneCount++;
                }
            }

            // Keyed by MDT npc index rather than npc id: MDT defines the same npc id twice in some dungeons,
            // and those two entries may carry different counts.
            $mdtNpcCountsByIndex = [];
            foreach ($mdtNpcs as $mdtNpc) {
                $mdtNpcCountsByIndex[$mdtNpc->getIndex()] = $mdtNpc->getCount();
            }

            // We now know a list of clones that we want to display, convert those clones to TEMP enemies
            foreach ($npcClones as $npcId => $floorIndexes) {
                foreach ($floorIndexes as $floorId => $clones) {
                    foreach ($clones as $mdtCloneIndex => $clone) {
                        $enemy = new Enemy([
                            // Dummy so we can ID them later on
                            'id'            => ($npcId * 100000) + ($floorId * 100) + $mdtCloneIndex,
                            'floor_id'      => $floorId,
                            'enemy_pack_id' => (int)$clone['g'],
                            'npc_id'        => $npcId,
                            // All MDT_IDs are 1-indexed, because LUA
                            'mdt_id'    => $mdtCloneIndex,
                            'mdt_scale' => $clone['scale'] ?? null,
                            'mdt_x'     => $clone['x'],
                            'mdt_y'     => $clone['y'],
                            'lat'       => $clone['lat'],
                            'lng'       => $clone['lng'],
                            'teeming'   => isset($clone['teeming']) && $clone['teeming'] ? Enemy::TEEMING_VISIBLE : null,
                            'faction'   => isset($clone['faction']) ?
                                ((int)$clone['faction'] === 1 ? Faction::FACTION_HORDE : Faction::FACTION_ALLIANCE)
                                : 'any',
                            'enemy_forces_override'         => $this->getCloneEnemyForcesOverride($clone, $mdtNpcCountsByIndex),
                            'enemy_forces_override_teeming' => null,
                        ]);
                        // Special MDT fields which are not fillable
                        $enemy->mdt_npc_index = (int)$clone['mdtNpcIndex'];
                        $enemy->is_mdt        = true;
                        $enemy->enemy_id      = -1;

                        $enemy->setRelation('floor', $floors->get($floorId));
                        // We don't care for the npc's relationships here - just want to know if the NPC exists or not
                        $enemy->setRelation(
                            'npc',
                            $this->dungeon->npcs->firstWhere('id', $enemy->npc_id) ??
                            new Npc([
                                'name' => 'UNABLE TO FIND NPC!',
                                'id'   => $npcId,
                            ]),
                        );

                        if ($enemy->npc->isEmissary()) {
                            $enemy->seasonal_type = Enemy::SEASONAL_TYPE_BEGUILING;
                        }

                        if ($enemy->npc->isAwakened()) {
                            $enemy->seasonal_type = Enemy::SEASONAL_TYPE_AWAKENED;
                        }

                        if ($enemy->npc->isEncrypted()) {
                            $enemy->seasonal_type = Enemy::SEASONAL_TYPE_ENCRYPTED;
                        }

                        if (isset($clone['inspiring']) && $clone['inspiring']) {
                            $enemy->seasonal_type = Enemy::SEASONAL_TYPE_INSPIRING;
                        }

                        if (isset($clone['disguised']) && $clone['disguised']) {
                            $enemy->seasonal_type = Enemy::SEASONAL_TYPE_SHROUDED;
                            $enemy->lat += 2;
                            $enemy->lng += 2;
                        }

                        $enemies->push($enemy);
                    }
                }
            }

            return $enemies;
        }, config('keystoneguru.cache.mdt.ttl'));
    }

    /**
     * A clone may carry its own `count`, which supersedes its NPC's `count` for that clone alone - MDT 6.2.10
     * introduced this to give Temple of Sethraliss' G30 different enemy forces than G29. A clone that agrees
     * with its NPC needs no override: the per-NPC enemy forces already yield that value.
     *
     * @param array<string, mixed> $clone
     * @param array<int, int>      $mdtNpcCountsByIndex
     */
    private function getCloneEnemyForcesOverride(array $clone, array $mdtNpcCountsByIndex): ?int
    {
        if (!isset($clone['count'])) {
            return null;
        }

        $cloneCount = (int)$clone['count'];

        return $cloneCount === ($mdtNpcCountsByIndex[(int)$clone['mdtNpcIndex']] ?? null) ? null : $cloneCount;
    }

    /**
     * @throws Exception
     */
    private function getLua(): Lua
    {
        $lua = null;

        $mdtHome = base_path(
            sprintf(
                'vendor/nnoggie/%s',
                Conversion::isDungeonInMainlineMDT($this->dungeon) ? 'mythicdungeontools' : 'mdt-legacy',
            ),
        );
        $expansionName    = Conversion::getExpansionName($this->dungeon->key);
        $mdtExpansionName = Conversion::getMDTExpansionName($this->dungeon->key);

        $mdtDungeonName = Conversion::getMDTDungeonName($this->dungeon->key);
        if (!empty($mdtExpansionName) &&
            !empty($mdtDungeonName) &&
            Expansion::active()->where('shortname', $expansionName)->exists()) {
            $dungeonHome = sprintf('%s/%s', $mdtHome, $mdtExpansionName);

            $mdtDungeonNameFile = sprintf('%s/%s.lua', $dungeonHome, $mdtDungeonName);

            if (!file_exists($mdtDungeonNameFile)) {
                throw new Exception(sprintf('Unable to find file %s', $mdtDungeonNameFile));
            }

            $eval = '
                        local MDT = {}
                        MDT.AddonName = "MythicDungeonTools"
                        MDT.L = {atalTeemingNote = "", underrotVoidNote = "", tdBuffGateNote = "", wcmWorldquestNote = ""}
                        MDT.dungeonTotalCount = {}
                        MDT.mapInfo = {}
                        MDT.mapPOIs = {}
                        MDT.dungeonEnemies = {}
                        MDT.scaleMultiplier = {}
                        MDT.dungeonBosses = {}
                        MDT.dungeonList = {}
                        MDT.dungeonMaps = {}
                        MDT.dungeonSubLevels = {}
                        MDT.zoneIdToDungeonIdx = {}

                        local L = {}

                        function UnitFactionGroup(who)
                            return "Horde"
                        end
                        ' .
                // Some files require LibStub
                $this->getFileContentsWithoutBOM(base_path('app/Logic/MDT/Lua/LibStub.lua')) . PHP_EOL .
                // $this->getFileContentsWithoutBOM(sprintf('%s/Locales/enUS.lua', $mdtHome)) . PHP_EOL .
                $this->getFileContentsWithoutBOM($mdtDungeonNameFile) . PHP_EOL .
                // Insert dummy function to get what we need
                '
                        function GetDungeonTotalCount()
                            return MDT.dungeonTotalCount[dungeonIndex]
                        end

                        function GetMapPOIs()
                            return MDT.mapPOIs[dungeonIndex]
                        end

                        function GetDungeonEnemies()
                            return MDT.dungeonEnemies[dungeonIndex]
                        end

                        function GetDungeonIndex()
                            return dungeonIndex
                        end
                    ';

            $replaceStrings = [
                'local addonName = ...' => 'local addonName = "MythicDungeonTools"',
                'local _, MDT = ...'    => 'local MDT = MDT',
            ];
            foreach ($replaceStrings as $search => $replace) {
                $eval = str_replace($search, $replace, $eval);
            }

            $lua = new Lua();
            $lua->eval($eval);
        }

        return $lua;
    }

    private function getFileContentsWithoutBOM(string $filename): ?string
    {
        $contents = file_get_contents($filename) ?: null;

        if ($contents !== null) {
            // Remove BOM if present
            $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        }

        return $contents;
    }
}
