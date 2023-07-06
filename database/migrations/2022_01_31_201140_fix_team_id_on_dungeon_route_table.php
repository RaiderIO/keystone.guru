<?php

use App\Models\DungeonRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class FixTeamIdOnDungeonRouteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('team_id')->nullable(true)->change();
        });
        DungeonRoute::query()->where('team_id', '<=', 0)->update(['team_id' => null]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DungeonRoute::query()->whereNull('team_id')->update(['team_id' => -1]);
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('team_id')->nullable(false)->change();
        });
    }
}
