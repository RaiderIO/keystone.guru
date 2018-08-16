<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\DungeonRoute;

class AddPublicKeyColumnToDungeonRoutes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->string('public_key')->after('id');
        });

        // Set a public key for all current routes
        DungeonRoute::all()->each(function(DungeonRoute $route){
            $route->public_key = \App\Models\DungeonRoute::generateRandomPublicKey();
            $route->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('public_key');
        });
    }
}
