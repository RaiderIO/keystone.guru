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
        DB::table('enemy_packs')->get()->each(function ($packData, $key) {
            $vertices = DB::table('enemy_pack_vertices')->where('enemy_pack_id', $packData->id)->get();

            $objs = [];
            foreach ($vertices as $vertex) {
                $objs[] = ['lat' => $vertex->lat, 'lng' => $vertex->lng];
            }

            DB::table('enemy_packs')->where('id', $packData->id)->update(['vertices_json' => json_encode($objs)]);
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
