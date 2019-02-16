<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\EnemyPatrol;
use App\Models\Polyline;

class ConvertEnemyPatrolsToPolylines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create a new polyline for each patrol
        EnemyPatrol::all()->each(function($item, $key){
            /** @var EnemyPatrol $item */
            $polyline = new Polyline();
            $polyline->model_id = $item->id;
            $polyline->model_class = get_class($item);
            $polyline->color = '#E25D5D';
            $polyline->weight = 2;
            $polyline->vertices_json = $item->vertices_json;

            $polyline->save();
        });

        // Convert the patrols table
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropColumn('vertices_json');
            $table->integer('polyline_id')->after('enemy_id');
        });

        // Couple the polylines to the enemy patrols
        App\Models\Polyline::where('model_class', 'App\Models\EnemyPatrol')->each(function($item, $key){
            /** @var EnemyPatrol $enemyPatrol */
            $enemyPatrol = App\Models\EnemyPatrol::findOrFail($item->model_id);
            $enemyPatrol->polyline_id = $item->id;
            $enemyPatrol->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('polylines', function (Blueprint $table) {
            //
        });
    }
}
