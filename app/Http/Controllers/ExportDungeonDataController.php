<?php

namespace App\Http\Controllers;

use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonStartMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPackVertex;
use App\Models\EnemyPatrol;
use App\Models\EnemyPatrolVertex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ExportDungeonDataController extends Controller
{

    /**
     * @param $model Model
     * @return string
     */
    private function _getExportedLine($model)
    {
        $attributes = $model->getAttributes();
        foreach($attributes as $key => $value){
            if($attributes[$key] === null ){
                $attributes[$key] = 'NULL';
            } else if(is_string($attributes[$key])) {
                $attributes[$key] = sprintf('\'%s\'', $attributes[$key]);
            }
        }
        return sprintf("INSERT INTO %s (%s) VALUES(%s)", $model->getTable(), implode(', ', array_keys($attributes)), implode(', ', array_values($attributes)));
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function submit(Request $request)
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::findOrFail($request->get('dungeon_id', 0));

        $result = array();

        $tables = [
            (new DungeonFloorSwitchMarker())->getTable(),
            (new DungeonStartMarker())->getTable(),

            (new Enemy())->getTable(),
            (new EnemyPack())->getTable(),
            (new EnemyPackVertex())->getTable(),
            (new EnemyPatrol())->getTable(),
            (new EnemyPatrolVertex())->getTable(),

        ];

        foreach ($dungeon->floors as $floor) {
            $enemies = Enemy::all()->where('floor_id', '=', $floor->id);

            $result[] = '';
            $result[] = sprintf('/** %s - %s - Enemies */', $dungeon->name, $floor->name);
            $result[] = '';
            $result[] = sprintf('DELETE FROM %s WHERE `floor_id` = \'%s\'', (new Enemy())->getTable(), $floor->id);
            foreach ($enemies as $enemy) {
                $result[] = $this->_getExportedLine($enemy);
            }

            // new line
            $enemyPacks = EnemyPack::all()->where('floor_id', '=', $floor->id);

            $result[] = '';
            $result[] = sprintf('/** %s - %s - Enemy Packs */', $dungeon->name, $floor->name);
            $result[] = '';

            $result[] = sprintf('DELETE FROM %s WHERE `floor_id` = \'%s\'', (new EnemyPack())->getTable(), $floor->id);
            foreach ($enemyPacks as $enemyPack) {
            $result[] = sprintf('DELETE FROM %s WHERE `enemy_pack_id` = \'%s\'', (new EnemyPackVertex())->getTable(), $enemyPack->id);
                /** @var $enemyPack EnemyPack */
                $result[] = $this->_getExportedLine($enemyPack);
                foreach($enemyPack->vertices as $vertex){
                    $result[] = $this->_getExportedLine($vertex);
                }
            }

//            foreach($tables as $table){
//                $modelsToExport = DB::table($table)->where('floor_id', '=', $floor->id)->get();
//                    dd($modelsToExport);
//                if( count($modelsToExport) > 0 ){
//                }
//            }
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
