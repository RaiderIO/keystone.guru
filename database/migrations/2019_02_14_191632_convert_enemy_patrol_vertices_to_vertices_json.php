<?php

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
        DB::table('enemy_patrols')->get()->each(function ($patrolData, $key) {
            $vertices = DB::table('enemy_patrol_vertices')->where('enemy_patrol_id', $patrolData->id)->get();

            $objs = [];
            foreach ($vertices as $vertex) {
                $objs[] = ['lat' => $vertex->lat, 'lng' => $vertex->lng];
            }

            DB::table('enemy_patrols')->where('id', $patrolData->id)->update(['vertices_json' => json_encode($objs)]);
        });
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
