<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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
        foreach(\App\Models\Path::all() as $path){
            $vertices = DB::table('path_vertices')->where('path_id', $path->id)->get();

            $objs = [];
            foreach($vertices as $vertex){
                $objs[] = ['lat' => $vertex->lat, 'lng' => $vertex->lng];
            }

            $path->vertices_json = json_encode($objs);
            $path->save();
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
