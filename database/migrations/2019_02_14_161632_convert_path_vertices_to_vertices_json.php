<?php

use Illuminate\Database\Migrations\Migration;

class ConvertPathVerticesToVerticesJson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Convert all paths to the new structure
        DB::table('paths')->get()->each(function ($pathData, $key) {
            $vertices = DB::table('path_vertices')->where('path_id', $pathData->id)->get();

            $objs = [];
            foreach ($vertices as $vertex) {
                $objs[] = ['lat' => $vertex->lat, 'lng' => $vertex->lng];
            }

            DB::table('paths')->where('id', $pathData->id)->update(['vertices_json' => json_encode($objs)]);
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
