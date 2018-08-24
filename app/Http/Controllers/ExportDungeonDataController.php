<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonStartMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor;
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
            /** @var Dungeon $dungeon */
            foreach ($dungeon->floors as $floor) {
                /** @var Floor $floor */
                $enemies = Enemy::all()->where('floor_id', '=', $floor->id);
                $enemyPacks = EnemyPack::all()->where('floor_id', '=', $floor->id);
                $enemyPatrols = EnemyPatrol::all()->where('floor_id', '=', $floor->id);
                $dungeonStartMarkers = DungeonStartMarker::all()->where('floor_id', '=', $floor->id);
                $dungeonFloorSwitchMarkers = DungeonFloorSwitchMarker::all()->where('floor_id', '=', $floor->id);

                $result['enemies'] = $enemies;
                $result['enemy_packs'] = $enemyPacks;
                $result['enemy_patrols'] = $enemyPatrols;
                $result['dungeon_start_markers'] = $dungeonStartMarkers;
                $result['dungeon_floor_switch_markers'] = $dungeonFloorSwitchMarkers;

                foreach ($result as $category => $categoryData) {
                    $filePath = storage_path() . '/dungeondata/' . $dungeon->key . '/' . $category . '.json';
                    mkdir($filePath, 755, true);
                    $file = fopen($filePath, 'w') or die('Cannot create file');
                    fwrite($file, json_encode($categoryData, JSON_PRETTY_PRINT));
                    fclose($file);
                }
            }
        }

        dd($result);


        return view('admin.datadump.viewexporteddungeondata', ['data' => $result]);
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
