<?php

namespace App\Http\Controllers;

use App\Models\Brushline;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute;
use App\Models\DungeonStartMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\MapComment;
use App\Models\Npc;
use App\Models\Path;
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
            /** @var $dungeon Dungeon */
            // HoV is our test dungeon so keep there here so I don't have to rewrite this every time I want to debug
//            if( $dungeon->getKeyAttribute() !== 'hallsofvalor' ){
//                continue;
//            }

            $rootDirPath = database_path('/seeds/dungeondata/' . $dungeon->expansion->shortname . '/' . $dungeon->key);

            // Demo routes, load it in a specific way to make it easier to import it back in again
            $demoRoutes = $dungeon->dungeonroutes->where('demo', true)->values();
            foreach ($demoRoutes as $demoRoute) {
                /** @var $demoRoute DungeonRoute */
                unset($demoRoute->relations);
                // Do not reload them
                $demoRoute->setAppends([]);
                // Ids cannot be guaranteed with users uploading dungeonroutes as well. As such, a new internal ID must be created
                // for each and every re-import
                $demoRoute->setHidden(['id']);
                $demoRoute->load(['playerspecializations', 'playerraces', 'playerclasses',
                    'routeattributesraw', 'affixgroups', 'brushlines', 'paths', 'killzones', 'enemyraidmarkers', 'mapcomments']);

                // Routes and killzone IDs (and dungeonRouteIDs) are not determined by me, users will be adding routes and killzones.
                // I cannot serialize the IDs in the dev environment and expect it to be the same on the production instance
                // Thus, remove the IDs from both Paths and KillZones as we need to make new IDs when the DungeonRoute
                // is imported into the production environment
                $toHide = new Collection();
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
                    $item->setVisible(['floor_id', 'polyline']);
                    $toHide->add($item);
                }
                foreach ($demoRoute->killzones as $item) {
                    // Hidden by default to save data
                    $item->addVisible(['floor_id']);
                    $toHide->add($item);
                }
                foreach ($demoRoute->enemyraidmarkers as $item) {
                    $toHide->add($item);
                }
                foreach ($demoRoute->mapcomments as $item) {
                    $toHide->add($item);
                }
                foreach ($toHide as $item) {
                    /** @var $item Model */
                    $item->makeHidden(['id', 'dungeon_route_id']);
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
                // Map comments can ALSO be added by users, thus we never know where this thing comes. As such, insert it
                // at the end of the table instead.
                $mapComments->makeHidden(['id']);

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

        return view('admin.tools.datadump.viewexporteddungeondata', ['data' => $result]);
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
}
