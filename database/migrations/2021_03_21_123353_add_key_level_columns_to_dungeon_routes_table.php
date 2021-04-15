<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeyLevelColumnsToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('level_max')->default(config('keystoneguru.levels.max'))->after('description');
            $table->integer('level_min')->default(config('keystoneguru.levels.min'))->after('description');

            $table->index(['level_min', 'level_max']);
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
            $table->dropIndex('dungeon_routes_level_min_level_max_index');
            $table->dropColumn('level_max');
            $table->dropColumn('level_min');
        });
    }
}
