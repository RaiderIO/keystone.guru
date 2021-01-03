<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublishedStateIdColumnToDungeonRoutesTable extends Migration
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
            $table->integer('published_state_id')->default(1)->after('published');
        });

        DB::update('update dungeon_routes SET published_state_id = 3 WHERE published = true;');
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
            $table->dropColumn('published_state_id');
        });
    }
}
