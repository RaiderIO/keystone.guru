<?php

namespace App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\MapIcon;
use App\Models\Mapping\MappingChangeLog;
use App\Models\Npc;
use App\Models\Spell;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class Restore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:restore {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores a part of the mapping based on the mapping_change_logs table. I once messed up and needed this.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = (int)$this->argument('id');

        $changeLogs = MappingChangeLog::where('id', '>=', $id)->get();

        foreach ($changeLogs as $changeLog) {

            try {
                if ($changeLog->model_class === 'App\Models\Enemy') {
                    // This mob was marked as inspiring
                    if (strpos($changeLog->after_model, 'inspiring') !== false) {
                        $enemy = Enemy::findOrFail($changeLog->model_id);
                        $enemy->seasonal_type = 'inspiring';
                        $enemy->save();
                        $this->info(sprintf('Successfully restored %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
                    }
                } else {
                    // If JSON parsed properly
                    $properties = json_decode($changeLog->after_model);
                    if ($properties !== null) {
                        /** @var Model $modelClass */
                        $modelClass = new $changeLog->model_class;
                        foreach ($properties as $property => $value) {
                            // Prevent 'this column does not exist' errors -> https://stackoverflow.com/questions/51703381/check-if-column-exist-in-laravel-models-table-and-then-apply-condition
                            if ($modelClass->getConnection()->getSchemaBuilder()->hasColumn($modelClass->getTable(), $property)) {
                                // We don't control the IDs of map icons
                                if ($property === 'id' && $changeLog->model_class === 'App\Models\MapIcon') {
                                    continue;
                                }
                                $modelClass->$property = $value;
                            }
                        }
                        $modelClass->save();
                        $this->info(sprintf('Successfully restored %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
                    } else {
                        $this->error(sprintf('Unable to restore model %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
                    }
                }

            } catch (\Exception $ex) {

                $this->error(sprintf('Unable to restore model %s -> ID = %s', $changeLog->model_class, $changeLog->model_id));
            }

        }


        return 0;
    }

    /**
     * @param $dungeonDataDir string
     */
    private function _saveDungeons(string $dungeonDataDir)
    {
        // Save all dungeons
        $dungeons = Dungeon::without(['expansion'])->with('floors.floorcouplings')->get();

        $this->saveDataToJsonFile(
            $dungeons->makeHidden(['active', 'floor_count', 'expansion'])
                ->makeVisible(['expansion_id'])
                ->toArray(),
            $dungeonDataDir, 'dungeons.json');
    }

    /**
     * @param $dungeonDataDir string
     */
    private function _saveNpcs(string $dungeonDataDir)
    {
        // Save all NPCs which aren't directly tied to a dungeon
        $npcs = Npc::without(['spells'])->with(['npcspells'])->where('dungeon_id', -1)->get()->values();
        $npcs->makeHidden(['type', 'class']);
        foreach ($npcs as $item) {
            $item->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
        }

        // Save NPC data in the root of folder
        $this->info('Saving global NPCs');
        $this->saveDataToJsonFile($npcs->toArray(), $dungeonDataDir, 'npcs.json');
    }

    /**
     * @param $dungeonDataDir string
     */
    private function _saveSpells(string $dungeonDataDir)
    {
        // Save all spells
        $this->info('Saving Spells');
        $this->saveDataToJsonFile(Spell::all()->toArray(), $dungeonDataDir, 'spells.json');
    }

    /**
     * @param $dungeonDataDir string
     */
    private function _saveDungeonData(string $dungeonDataDir)
    {

        foreach (Dungeon::all() as $dungeon) {
            $this->info(sprintf('- Saving dungeon %s', $dungeon->name));
            /** @var $dungeon Dungeon */
            // HoV is our test dungeon so keep there here so I don't have to rewrite this every time I want to debug
//            if( $dungeon->getKeyAttribute() !== 'hallsofvalor' ){
//                continue;
//            }

            $rootDirPath = $dungeonDataDir . $dungeon->expansion->shortname . '/' . $dungeon->key;

            // Demo routes, load it in a specific way to make it easier to import it back in again
            $demoRoutes = $dungeon->dungeonroutes->where('demo', true)->values();
            foreach ($demoRoutes as $demoRoute) {
                /** @var $demoRoute DungeonRoute */
                unset($demoRoute->relations);
                // Do not reload them
                $demoRoute->setAppends([]);
                // Ids cannot be guaranteed with users uploading dungeonroutes as well. As such, a new internal ID must be created
                // for each and every re-import
                $demoRoute->setHidden(['id', 'thumbnail_updated_at']);
                $demoRoute->load(['playerspecializations', 'playerraces', 'playerclasses',
                                  'routeattributesraw', 'affixgroups', 'brushlines', 'paths', 'killzones', 'enemyraidmarkers',
                                  'pridefulenemies', 'mapicons']);

                // Routes and killzone IDs (and dungeonRouteIDs) are not determined by me, users will be adding routes and killzones.
                // I cannot serialize the IDs in the dev environment and expect it to be the same on the production instance
                // Thus, remove the IDs from both Paths and KillZones as we need to make new IDs when the DungeonRoute
                // is imported into the production environment
                $toHide = new \Illuminate\Database\Eloquent\Collection();
                // No ->merge() :( -> https://medium.com/@tadaspaplauskas/quick-tip-laravel-eloquent-collections-merge-gotcha-moment-e2a56fc95889
                foreach ($demoRoute->playerspecializations as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->playerraces as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->playerclasses as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->routeattributesraw as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->affixgroups as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->brushlines as $item) {
                    $item->setVisible(['floor_id', 'polyline']);
                    $toHide->add($item);
                }
                foreach ($demoRoute->paths as $item) {
                    $item->load(['linkedawakenedobelisks']);
                    $item->setVisible(['floor_id', 'polyline', 'linkedawakenedobelisks']);
                    $toHide->add($item);
                }
                foreach ($demoRoute->killzones as $item) {
                    // Hidden by default to save data
                    $item->makeVisible(['floor_id']);
                    $toHide->add($item);
                }
                foreach ($demoRoute->enemyraidmarkers as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->pridefulenemies as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->mapicons as $item) {
                    $item->load(['linkedawakenedobelisks']);
                    $item->setVisible(['floor_id', 'map_icon_type_id', 'lat', 'lng', 'comment', 'permanent_tooltip', 'seasonal_index', 'linkedawakenedobelisks']);
                    $toHide->add($item);
                }
                foreach ($toHide as $item) {
                    /** @var $item Model */
                    $item->makeHidden(['id', 'dungeon_route_id']);
                }
            }

            if ($demoRoutes->count() > 0) {
                $this->info(sprintf('-- Saving %s dungeonroutes', $demoRoutes->count()));
            }
            $this->saveDataToJsonFile($demoRoutes->toArray(), $rootDirPath, 'dungeonroutes.json');

            $npcs = Npc::without(['spells'])->with(['npcspells'])->where('dungeon_id', $dungeon->id)->get()->values();
            $npcs->makeHidden(['type', 'class']);
            foreach ($npcs as $item) {
                $item->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
            }

            // Save NPC data in the root of the dungeon folder
            if ($npcs->count() > 0) {
                $this->info(sprintf('-- Saving %s npcs', $npcs->count()));
            }
            $this->saveDataToJsonFile($npcs, $rootDirPath, 'npcs.json');

            /** @var Dungeon $dungeon */
            foreach ($dungeon->floors as $floor) {
                $this->info(sprintf('-- Saving floor %s', $floor->name));
                /** @var Floor $floor */
                // Only export NPC->id, no need to store the full npc in the enemy
                $enemies = Enemy::where('floor_id', $floor->id)->without(['npc', 'type'])->with('npc:id')->get()->values();
                foreach ($enemies as $enemy) {
                    /** @var $enemy Enemy */
                    if ($enemy->npc !== null) {
                        $enemy->npc->unsetRelation('spells');
                        $enemy->npc->unsetRelation('npcbolsteringwhitelists');
                        $enemy->npc->unsetRelation('type');
                        $enemy->npc->unsetRelation('class');
                    }
                }
                $enemyPacks = EnemyPack::where('floor_id', $floor->id)->get()->values();
                $enemyPatrols = EnemyPatrol::where('floor_id', $floor->id)->get()->values();
                $dungeonFloorSwitchMarkers = DungeonFloorSwitchMarker::where('floor_id', $floor->id)->get()->values();
                // Direction is an attributed column which does not exist in the database; it exists in the DungeonData seeder
                $dungeonFloorSwitchMarkers->makeHidden(['direction']);
                $mapIcons = MapIcon::where('floor_id', $floor->id)->where('dungeon_route_id', -1)->get()->values();
                // Map icons can ALSO be added by users, thus we never know where this thing comes. As such, insert it
                // at the end of the table instead.
                $mapIcons->makeHidden(['id', 'linked_awakened_obelisk_id']);

                $result['enemies'] = $enemies;
                $result['enemy_packs'] = $enemyPacks;
                $result['enemy_patrols'] = $enemyPatrols;
                $result['dungeon_floor_switch_markers'] = $dungeonFloorSwitchMarkers;
                $result['map_icons'] = $mapIcons;

                foreach ($result as $category => $categoryData) {
                    // Save enemies, packs, patrols, markers on a per-floor basis
                    if ($categoryData->count() > 0) {
                        $this->info(sprintf('--- Saving %s %s', $categoryData->count(), $category));
                    }
                    $this->saveDataToJsonFile($categoryData, $rootDirPath . '/' . $floor->index, $category . '.json');
                }
            }
        }
    }
}
