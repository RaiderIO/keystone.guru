<?php

use Illuminate\Database\Migrations\Migration;

class ConvertEnemyPackVerticesToVerticesJson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Convert all paths to the new structure
        foreach (\App\Models\EnemyPack::all() as $enemyPack) {
            $vertices = DB::table('enemy_pack_vertices')->where('enemy_pack_id', $enemyPack->id)->get();

            $objs = [];
            foreach ($vertices as $vertex) {
                $objs[] = ['lat' => $vertex->lat, 'lng' => $vertex->lng];
            }

            $enemyPack->vertices_json = json_encode($objs);
            $enemyPack->save();
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
