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
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Support\Collection;
use Lua;
use LuaException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @package App\Logic\MDT\Data
 * @author Wouter
 * @since 05/01/2019
 */
class MDTDungeon
{
    private Dungeon $dungeon;

    private CacheServiceInterface $cacheService;

    private CoordinatesServiceInterface $coordinatesService;

    function __construct(
        CacheServiceInterface       $cacheService,
        CoordinatesServiceInterface $coordinatesService,
        Dungeon                     $dungeon
    ) {
        $this->cacheService       = $cacheService;
        $this->coordinatesService = $coordinatesService;
        $this->dungeon            = $dungeon;


        if (!Conversion::hasMDTDungeonName($this->dungeon->key)) {
            throw new InvalidMDTDungeonException(sprintf('Unsupported MDT dungeon for dungeon key %s!', $this->dungeon->key));
        }
    }

    /**
     * @return array{normal: int, teeming: int, teemingEnabled: bool}
     * @throws Exception
     */
    public function getDungeonTotalCount(): array
    {
        $lua               = $this->getLua();
        $dungeonTotalCount = $lua->call('GetDungeonTotalCount');

        return [
            'normal'         => (int)$dungeonTotalCount['normal'],
            'teeming'        => (int)$dungeonTotalCount['teeming'],
            'teemingEnabled' => $dungeonTotalCount['teemingEnabled'],
        ];
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getMDTDungeonID(): int
    {
        $lua = $this->getLua();

        return $lua->call('GetDungeonIndex');
    }

    /**
     * Get a list of NPCs
     * @return Collection|MDTNpc[]
     * @throws Exception
     */
    public function getMDTNPCs(): Collection
    {
        return $this->cacheService->remember(sprintf('mdt_npcs_%s', $this->dungeon->key), function () {
            $mdtNpcs = new Collection();

            $lua           = $this->getLua();
            $rawMdtEnemies = $lua->call('GetDungeonEnemies');

            foreach ($rawMdtEnemies as $mdtNpcIndex => $mdtNpc) {
                $mdtNpcs->push(new MDTNpc((int)$mdtNpcIndex, $mdtNpc));
            }

            return $mdtNpcs;
        }, config('keystoneguru.cache.mdt.ttl'));
    }

    /**
     * @return Collection|MDTMapPOI[]
     * @throws Exception
     */
    public function getMDTMapPOIs(): Collection
    {
        $lua           = $this->getLua();
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
     * @param MappingVersion   $mappingVersion
     * @param Floor|Collection $floors The floors that you want to get the clones for.
     * @return Collection|Enemy[]
     */
    public function getClonesAsEnemies(MappingVersion $mappingVersion, Collection $floors): Collection
    {
        return $this->cacheService->remember(sprintf('mdt_enemies_%s', $this->dungeon->key), function () use ($mappingVersion, $floors) {
            $enemies = new Collection();

            try {
                $mdtNpcs = $this->getMDTNPCs();
            } catch (Exception $exception) {
                logger()->error($exception->getMessage());

                return $enemies;
            }

            // Ensure floors is a collection
            if (!($floors instanceof Collection)) {
                $floors = [$floors];
            }

            // A bit of a hack, but it works. If we have a floor with a facade in it, we only parse THAT floor
            // since that's the only floor that MDT will have. We will then put the enemies in the correct floors.
            // Pinky promise.
            $facadeFloors = $floors->filter(function (Floor $floor) {
                return $floor->facade;
            });

            if ($facadeFloors->isNotEmpty()) {
                $floors = $facadeFloors;
            }

            $floors->load(['dungeon']);

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
                            $clone['g'] = $clone['g'] ?? -1;

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

            // We now know a list of clones that we want to display, convert those clones to TEMP enemies

            foreach ($npcClones as $npcId => $floorIndexes) {
                foreach ($floorIndexes as $floorId => $clones) {
                    foreach ($clones as $mdtCloneIndex => $clone) {
                        $enemy = new Enemy([
                            // Dummy so we can ID them later on
                            'id'                            => ($npcId * 100000) + ($floorId * 100) + $mdtCloneIndex,
                            'floor_id'                      => $floorId,
                            'enemy_pack_id'                 => (int)$clone['g'],
                            'npc_id'                        => $npcId,
                            // All MDT_IDs are 1-indexed, because LUA
                            'mdt_id'                        => $mdtCloneIndex,
                            'lat'                           => $clone['lat'],
                            'lng'                           => $clone['lng'],
                            'teeming'                       => isset($clone['teeming']) && $clone['teeming'] ? Enemy::TEEMING_VISIBLE : null,
                            'faction'                       => isset($clone['faction']) ?
                                ((int)$clone['faction'] === 1 ? Faction::FACTION_HORDE : Faction::FACTION_ALLIANCE)
                                : 'any',
                            'enemy_forces_override'         => null,
                            'enemy_forces_override_teeming' => null,
                        ]);
                        // Special MDT fields which are not fillable
                        $enemy->mdt_npc_index = (int)$clone['mdtNpcIndex'];
                        $enemy->is_mdt        = true;
                        $enemy->enemy_id      = -1;

                        $enemy->npc = $this->dungeon->npcs->firstWhere('id', $enemy->npc_id);

                        if ($enemy->npc === null) {
                            $enemy->npc = new Npc(['name' => 'UNABLE TO FIND NPC!', 'id' => $npcId, 'dungeon_id' => -1, 'base_health' => 76000, 'enemy_forces' => -1]);
                        }

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
                            $enemy->lat           += 2;
                            $enemy->lng           += 2;
                        }

                        $enemies->push($enemy);
                    }
                }
            }

            return $enemies;
        }, config('keystoneguru.cache.mdt.ttl'));
    }

    /**
     * @return Lua
     * @throws Exception
     */
    private function getLua(): Lua
    {
        $lua = null;

        $mdtHome          = base_path('vendor/nnoggie/mythicdungeontools');
        $expansionName    = Conversion::getExpansionName($this->dungeon->key);
        $mdtExpansionName = Conversion::getMDTExpansionName($this->dungeon->key);

        $mdtDungeonName = Conversion::getMDTDungeonName($this->dungeon->key);
        if (!empty($mdtExpansionName) && !empty($mdtDungeonName) && Expansion::active()->where('shortname', $expansionName)->exists()) {
            $dungeonHome = sprintf('%s/%s', $mdtHome, $mdtExpansionName);

            $mdtDungeonNameFile = sprintf('%s/%s.lua', $dungeonHome, $mdtDungeonName);

            if (!file_exists($mdtDungeonNameFile)) {
                throw new Exception(sprintf('Unable to find file %s', $mdtDungeonNameFile));
            }

            $eval = '
                        local MDT = {}
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
                        ' .
                // Some files require LibStub
                file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')) . PHP_EOL .
                // file_get_contents(sprintf('%s/Locales/enUS.lua', $mdtHome)) . PHP_EOL .
                file_get_contents($mdtDungeonNameFile) . PHP_EOL .
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

            try {
                $lua = new Lua();
                $lua->eval($eval);
            } catch (LuaException $ex) {
                dd($ex, $expansionName, $mdtDungeonName, $eval);
            }
        }

        return $lua;
    }
}
