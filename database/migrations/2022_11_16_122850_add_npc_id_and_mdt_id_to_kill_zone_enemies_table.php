<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNpcIdAndMdtIdToKillZoneEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->integer('mdt_id')->default(null)->nullable()->after('kill_zone_id');
            $table->integer('npc_id')->default(null)->nullable()->after('kill_zone_id');

            $table->index(['npc_id', 'mdt_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kill_zone_enemies', function (Blueprint $table) {
            $table->index(['npc_id', 'mdt_id']);

            $table->dropColumn('mdt_id');
            $table->dropColumn('npc_id');
        });
    }
}
