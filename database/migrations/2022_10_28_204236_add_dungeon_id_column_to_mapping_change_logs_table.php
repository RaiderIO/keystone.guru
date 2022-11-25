<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDungeonIdColumnToMappingChangeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapping_change_logs', function (Blueprint $table) {
            $table->integer('dungeon_id')->after('id')->nullable(true);

            $table->index(['dungeon_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapping_change_logs', function (Blueprint $table) {
            $table->dropColumn('dungeon_id');
        });
    }
}
