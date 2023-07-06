<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnemyPatrolIdColumnToEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('enemy_patrol_id')->nullable()->default(null)->after('enemy_pack_id');

            $table->index(['enemy_patrol_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropIndex(['enemy_patrol_id']);
            $table->dropColumn('enemy_patrol_id');
        });
    }
}
