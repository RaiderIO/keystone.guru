<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConvertPolylineToBrushline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Convert all existing polylines to a brushline
        \App\Models\Polyline::all()->each(function($item, $key){
            /** @var \App\Models\Polyline $item */
            $brushline = new \App\Models\Brushline();
            // dungeon_route_id and floor_id are columns when this migration is ran
            $brushline->dungeon_route_id = $item->dungeon_route_id;
            $brushline->floor_id = $item->floor_id;
            $brushline->polyline_id = $item->id;
            $brushline->created_at = $item->created_at;
            $brushline->updated_at = $item->updated_at;

            $brushline->save();
        });

        // Convert polyline table to be more generic instead
        Schema::table('polylines', function (Blueprint $table) {
            // All current
            $table->dropColumn('dungeon_route_id');
            $table->dropColumn('floor_id');
            $table->dropColumn('type');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
            $table->integer('model_id')->after('id');
            $table->string('model_class')->after('model_id');
        });

        // Couple the polylines to their brushlines (the only items that exist at the moment)
        \App\Models\Brushline::all()->each(function($item, $key){
            /** @var \App\Models\Brushline $item */
            /** @var \App\Models\Polyline $polyline */
            $polyline = \App\Models\Polyline::findOrFail($item->polyline_id);
            $polyline->model_id = $item->id;
            $polyline->model_class = get_class($item);
            $polyline->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Err yeah, no
    }
}
