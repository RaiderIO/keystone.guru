<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertEnemyPatrolVerticesToVerticesJson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Convert all paths to the new structure
        foreach(\App\Models\EnemyPatrol::all() as $enemyPatrol){
            $vertices = DB::table('enemy_patrol_vertices')->where('enemy_patrol_id', $enemyPatrol->id)->get();

            $objs = [];
            foreach($vertices as $vertex){
                $objs[] = ['lat' => $vertex->lat, 'lng' => $vertex->lng];
            }

            $enemyPatrol->vertices_json = json_encode($objs);
            $enemyPatrol->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No need to go down really
    }
}
