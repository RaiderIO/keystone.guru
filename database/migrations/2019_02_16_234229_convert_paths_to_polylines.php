<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Path;
use App\Models\Polyline;

class ConvertPathsToPolylines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create a new polyline for each patrol
        Path::all()->each(function($item, $key){
            /** @var Path $item */
            $polyline = new Polyline();
            $polyline->model_id = $item->id;
            $polyline->model_class = get_class($item);
            $polyline->color = $item->color;
            $polyline->weight = 3;
            $polyline->vertices_json = $item->vertices_json;

            $polyline->save();
        });

        // Convert the patrols table
        Schema::table('paths', function (Blueprint $table) {
            $table->dropColumn('color');
            $table->dropColumn('vertices_json');
            $table->integer('polyline_id')->after('floor_id');
        });

        // Couple the polylines to the enemy patrols
        App\Models\Polyline::where('model_class', 'App\Models\Path')->each(function($item, $key){
            /** @var Path $path */
            $path = App\Models\Path::findOrFail($item->model_id);
            $path->polyline_id = $item->id;
            $path->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
