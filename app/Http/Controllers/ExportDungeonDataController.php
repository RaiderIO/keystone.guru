<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRouteEnemyRaidMarker;
use App\Models\DungeonStartMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\MapComment;
use App\Models\Npc;
use App\Models\Route;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExportDungeonDataController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function submit(Request $request)
    {
        $result = array();

        foreach (Dungeon::all() as $dungeon) {
            // HoV is our test dungeon so keep there here so I don't have to rewrite this every time I want to debug
//            if( $dungeon->getKeyAttribute() !== 'hallsofvalor' ){
//                continue;
//            }

            /** @var $dungeon Dungeon */
            $rootDirPath = storage_path() . '/dungeondata/' . $dungeon->expansion->shortname . '/' . $dungeon->key;

            // Demo routes, load it in a specific way to make it easier to import it back in again
            $demoRoutes = $dungeon->dungeonroutes->where('demo', true)->values();
            foreach ($demoRoutes as $demoRoute) {
                /** @var $demoRoute Model */
                unset($demoRoute->relations);
                // Do not reload them
                $demoRoute->setAppends([]);
                // Ids cannot be guaranteed with users uploading dungeonroutes as well. As such, a new internal ID must be created
                // for each and every re-import
                $demoRoute->setHidden(['id']);
                $demoRoute->load(['playerraces', 'playerclasses', 'affixgroups', 'routes', 'killzones', 'enemyraidmarkers', 'mapcomments']);

                // Routes and killzone IDs (and dungeonRouteIDs) are not determined by me, users will be adding routes and killzones.
                // I cannot serialize the IDs in the dev environment and expect it to be the same on the production instance
                // Thus, remove the IDs from both Routes and KillZones as we need to make new IDs when the DungeonRoute
                // is imported into the production environment
                foreach($demoRoute->routes as $route){
                    /** @var $route Route */
                    $route->makeHidden(['id', 'dungeon_route_id']);
                }

                foreach($demoRoute->killzones as $killzone){
                    /** @var $killzone KillZone */
                    $killzone->makeHidden(['id', 'dungeon_route_id']);
                }

                foreach($demoRoute->enemyraidmarkers as $enemyraidmarker){
                    /** @var $enemyraidmarker DungeonRouteEnemyRaidMarker */
                    $enemyraidmarker->makeHidden(['id', 'dungeon_route_id']);
                }

                foreach($demoRoute->mapcomments as $mapcomment){
                    /** @var $mapcomment MapComment */
                    $mapcomment->makeHidden(['id', 'dungeon_route_id']);
                }
            }

            $this->_saveData($demoRoutes, $rootDirPath, 'dungeonroutes.json');

            $npcs = Npc::all()->where('dungeon_id', $dungeon->id)->values();

            // Save NPC data in the root of the dungeon folder
            $this->_saveData($npcs, $rootDirPath, 'npcs.json');

            /** @var Dungeon $dungeon */
            foreach ($dungeon->floors as $floor) {
                /** @var Floor $floor */
                // Only export NPC->id, no need to store the full npc in the enemy
                $enemies = Enemy::where('floor_id', $floor->id)->without('npc')->with('npc:id')->get()->values();
                $enemyPacks = EnemyPack::where('floor_id', $floor->id)->get()->values();
                $enemyPatrols = EnemyPatrol::where('floor_id', $floor->id)->get()->values();
                $dungeonStartMarkers = DungeonStartMarker::where('floor_id', $floor->id)->get()->values();
                $dungeonFloorSwitchMarkers = DungeonFloorSwitchMarker::where('floor_id', $floor->id)->get()->values();
                $mapComments = MapComment::where('floor_id', $floor->id)->where('always_visible', true)->get()->values();

                $result['enemies'] = $enemies;
                $result['enemy_packs'] = $enemyPacks;
                $result['enemy_patrols'] = $enemyPatrols;
                $result['dungeon_start_markers'] = $dungeonStartMarkers;
                $result['dungeon_floor_switch_markers'] = $dungeonFloorSwitchMarkers;
                $result['map_comments'] = $mapComments;

                foreach ($result as $category => $categoryData) {
                    // Save enemies, packs, patrols, markers on a per-floor basis
                    $this->_saveData($categoryData, $rootDirPath . '/' . $floor->index, $category . '.json');
                }
            }
        }

        return view('admin.datadump.viewexporteddungeondata', ['data' => $result]);
    }

    /**
     * @param $dataArr Collection
     * @param $dir string
     * @param $filename string
     */
    private function _saveData($dataArr, $dir, $filename)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 755, true);
        }

        $filePath = $dir . '/' . $filename;
        $file = fopen($filePath, 'w') or die('Cannot create file');
        fwrite($file, json_encode($dataArr, JSON_PRETTY_PRINT));
        fclose($file);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function view()
    {
        return view('admin.datadump.exportdungeondata', ['dungeons' => Dungeon::all()]);
    }
}
