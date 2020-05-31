<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeasonalIndexToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->integer('seasonal_index')->after('difficulty')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dropColumn('seasonal_index');
        });
    }
}
