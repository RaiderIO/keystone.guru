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
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Npc;
use App\Service\Cache\CacheServiceInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Lua;
use LuaException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class ImportString. This file was created as a sort of copy of https://github.com/nnoggie/MythicDungeonTools/blob/master/Transmission.lua
 * All rights belong to their respective owners, I did write this but I did not make this up.  I merely translated the LUA
 * to PHP to allow for importing of the exported strings.
 * @package App\Logic\MDT
 * @author Wouter
 * @since 05/01/2019
 */
class MDTDungeon
{
    /** @var Dungeon */
    private Dungeon $dungeon;

    /** @var CacheServiceInterface|mixed */
    private CacheServiceInterface $cacheService;


    function __construct(Dungeon $dungeon)
    {
        $this->dungeon = $dungeon;

        $this->cacheService = App::make(CacheServiceInterface::class);

        if (!Conversion::hasMDTDungeonName($this->dungeon->key)) {
            throw new Exception(sprintf('Unsupported MDT dungeon for dungeon key %s!', $this->dungeon->key));
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

        foreach ($rawMdtMapPOIs as $subLevel => $pois) {
            foreach ($pois as $poiIndex => $poi) {
                $result->push(new MDTMapPOI((int)$subLevel, $poi));
            }
        }

        return $result;
    }

    /**
     * Get all clones of this dungeon in the format of enemies (Keystone.guru style).
     * @param Floor|Collection $floors The floors that you want to get the clones for.
     * @return Collection|Enemy[]
     * @throws InvalidArgumentException
     */
    public function getClonesAsEnemies(Collection $floors): Collection
    {
        return $this->cacheService->remember(sprintf('mdt_enemies_%s', $this->dungeon->key), function () use ($floors) {
            $enemies = new Collection();

            try {
                $mdtNpcs = $this->getMDTNPCs();
            } catch (Exception $exception) {
                return $enemies;
            }

            // Ensure floors is a collection
            if (!($floors instanceof Collection)) {
                $floors = [$floors];
            }

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
                            // Gets funky here. There's instances where MDT has defined an NPC with the same NPC_ID twice
                            // This fucks with the assignment below this if, because it'll overwrite the NPCs there.
                            // We don't want this; instead append it at the end of the current array at the proper index
                            // We calculate that at the hand of the current index in the second array ($cloneCount).
                            if (isset($npcClones[$npcId][$floor->id][$mdtCloneIndex])) {
                                $mdtCloneIndex += (count($npcClones[$npcId][$floor->id]) - $cloneCount);
                            }

                            // Append this clone to the array
                            $npcClones[$npcId][$floor->id][$mdtCloneIndex] = $clone;
                        }
                    }

                    $cloneCount++;
                }
            }

            // We now know a list of clones that we want to display, convert those clones to TEMP enemies

            /** @var Collection|Npc[] $npcs */
            $npcs = Npc::whereIn('dungeon_id', [$floors->first()->dungeon->id, -1])->get();
            foreach ($npcClones as $npcId => $floorIndexes) {
                foreach ($floorIndexes as $floorId => $clones) {
                    foreach ($clones as $mdtCloneIndex => $clone) {
                        $latLng = Conversion::convertMDTCoordinateToLatLng($clone);

                        $enemy = new Enemy([
                            // Dummy so we can ID them later on
                            'id'                            => ($npcId * 100000) + ($floorId * 100) + $mdtCloneIndex,
                            'floor_id'                      => $floorId,
                            'enemy_pack_id'                 => (int)$clone['g'],
                            'npc_id'                        => $npcId,
                            // All MDT_IDs are 1-indexed, because LUA
                            'mdt_id'                        => $mdtCloneIndex,
                            'teeming'                       => isset($clone['teeming']) && $clone['teeming'] ? Enemy::TEEMING_VISIBLE : null,
                            'faction'                       => isset($clone['faction']) ?
                                ((int)$clone['faction'] === 1 ? Faction::FACTION_HORDE : Faction::FACTION_ALLIANCE)
                                : 'any',
                            'enemy_forces_override'         => null,
                            'enemy_forces_override_teeming' => null,
                            'lat'                           => $latLng['lat'],
                            'lng'                           => $latLng['lng'],
                        ]);
                        // Special MDT fields which are not fillable
                        $enemy->mdt_npc_index = (int)$clone['mdtNpcIndex'];
                        $enemy->is_mdt        = true;
                        $enemy->enemy_id      = -1;

                        $enemy->npc = $npcs->firstWhere('id', $enemy->npc_id);

                        if ($enemy->npc === null) {
                            $enemy->npc = new Npc(['name' => 'UNABLE TO FIND NPC!', 'id' => $npcId, 'dungeon_id' => -1, 'base_health' => 76000, 'enemy_forces' => -1]);
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
