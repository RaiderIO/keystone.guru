<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUnlistedAndPublishedColumnsFromDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Schema::table('dungeon_routes', function (Blueprint $table)
            {
                $table->dropColumn('unlisted');
                $table->dropColumn('published');
            });
        } catch (Exception $ex) {
            logger()->warning('Unable to find unlisted/published columns - this is probably OK');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back
    }
}
