<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 06/01/2019
 * Time: 18:10
 */

namespace App\Logic\MDT\Data;


use App\Logic\MDT\Conversion;
use App\Logic\MDT\Entity\MDTNpc;
use App\Models\Enemy;
use App\Models\Expansion;
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

    /** @var string The Dungeon's name (Keystone.guru style). Can be converted using self::$dungeonMapping */
    private string $dungeonKey;

    /** @var CacheServiceInterface|mixed */
    private CacheServiceInterface $cacheService;


    function __construct($dungeonKey)
    {
        $this->dungeonKey = $dungeonKey;

        $this->cacheService = App::make(CacheServiceInterface::class);
    }

    /**
     * Get a list of NPCs
     * @return Collection|MDTNpc[]
     * @throws Exception
     */
    public function getMDTNPCs(): Collection
    {
        $result = new Collection();
        if (Conversion::hasMDTDungeonName($this->dungeonKey)) {
            // Fetch the cache or set it if it didn't exist
            $result = $this->cacheService->remember(sprintf('mdt_npcs_%s', $this->dungeonKey), function () {
                $result           = new Collection();
                $mdtHome          = base_path('vendor/nnoggie/mythicdungeontools');
                $expansionName    = Conversion::getExpansionName($this->dungeonKey);
                $mdtExpansionName = Conversion::getMDTExpansionName($this->dungeonKey);

                $mdtDungeonName = Conversion::getMDTDungeonName($this->dungeonKey);
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
                        function GetDungeonEnemies()
                            return MDT.dungeonEnemies[dungeonIndex]
                        end
                    ';

                    try {
                        $lua = new Lua();
                        $lua->eval($eval);
                        $rawMdtEnemies = $lua->call('GetDungeonEnemies');

                        foreach ($rawMdtEnemies as $mdtNpcIndex => $mdtNpc) {
                            $result->push(new MDTNpc((int)$mdtNpcIndex, $mdtNpc));
                        }
                    } catch (LuaException $ex) {
                        dd($ex, $expansionName, $mdtDungeonName, $eval);
                    }
                }
                return $result;
            }, config('keystoneguru.cache.mdt.ttl'));
        }

        return $result;
    }


    /**
     * Get all clones of this dungeon in the format of enemies (Keystone.guru style).
     * @param $floors Floor|Collection The floors that you want to get the clones for.
     * @return Collection|Enemy[]
     * @throws InvalidArgumentException
     */
    public function getClonesAsEnemies(Collection $floors): Collection
    {
        return $this->cacheService->remember(sprintf('mdt_enemies_%s', $this->dungeonKey), function () use ($floors) {
            $enemies = new Collection();

            try {
                $mdtNpcs = $this->getMDTNPCs();
            } catch (Exception $exception){
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
                            $clone['g'] = isset($clone['g']) ? $clone['g'] : -1;

                            $npcId = $mdtNpc->getId();
                            // Make sure array is set
                            if (!isset($npcClones[$npcId])) {
                                $npcClones[$npcId] = [];
                            }
                            // Gets funky here. There's instances where MDT has defined an NPC with the same NPC_ID twice
                            // This fucks with the assignment below this if, because it'll overwrite the NPCs there.
                            // We don't want this; instead append it at the end of the current array at the proper index
                            // We calculate that at the hand of the current index in the second array ($cloneCount).
                            if (isset($npcClones[$npcId][$mdtCloneIndex])) {
                                $mdtCloneIndex += (count($npcClones[$npcId]) - $cloneCount);
                            }
                            // Append this clone to the array
                            $npcClones[$npcId][$mdtCloneIndex] = $clone;
                        }
                    }

                    $cloneCount++;
                }
            }

            // We now know a list of clones that we want to display, convert those clones to TEMP enemies
            foreach ($floors as $floor) {
                /** @var Collection|Npc[] $npcs */
                $npcs = Npc::whereIn('dungeon_id', [$floor->dungeon->id, -1])->get();
                foreach ($npcClones as $npcId => $clones) {
                    foreach ($clones as $mdtCloneIndex => $clone) {
                        if ((int)$clone['sublevel'] === ($floor->mdt_sub_level ?? $floor->index)) {
                            $enemy = new Enemy();
                            // Dummy so we can ID them later on
                            $enemy->is_mdt        = true;
                            $enemy->floor_id      = $floor->id;
                            $enemy->enemy_pack_id = (int)$clone['g'];
                            $enemy->mdt_npc_index = (int)$clone['mdtNpcIndex'];
                            $enemy->npc_id        = $npcId;
                            // All MDT_IDs are 1-indexed, because LUA
                            $enemy->mdt_id                = $mdtCloneIndex;
                            $enemy->enemy_id              = -1;
                            $enemy->teeming               = isset($clone['teeming']) && $clone['teeming'] ? 'visible' : null;
                            $enemy->faction               = isset($clone['faction']) ? ((int)$clone['faction'] === 1 ? 'horde' : 'alliance') : 'any';
                            $enemy->enemy_forces_override = -1;

                            $latLng     = Conversion::convertMDTCoordinateToLatLng($clone);
                            $enemy->lat = $latLng['lat'];
                            $enemy->lng = $latLng['lng'];

                            $enemy->npc = $npcs->firstWhere('id', $enemy->npc_id);

                            if ($enemy->npc === null) {
                                $enemy->npc = new Npc(['name' => 'UNABLE TO FIND NPC!', 'id' => $npcId, 'dungeon_id' => -1, 'base_health' => 76000, 'enemy_forces' => -1]);
                            }

                            $enemies->push($enemy);
                        }
                    }
                }
            }

            return $enemies;
        }, config('keystoneguru.cache.mdt.ttl'));
    }
}
