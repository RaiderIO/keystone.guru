<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveEnemyIdColumnFromEnemyPatrolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropColumn('enemy_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->integer('enemy_id')->default(-1);
        });
    }
}
