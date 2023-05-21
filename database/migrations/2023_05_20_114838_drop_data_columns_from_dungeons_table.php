<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDataColumnsFromDungeonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('enemy_forces_required');
            $table->dropColumn('enemy_forces_required_teeming');
            $table->dropColumn('enemy_forces_shrouded');
            $table->dropColumn('enemy_forces_shrouded_zul_gamux');
            $table->dropColumn('timer_max_seconds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->integer('enemy_forces_required');
            $table->integer('enemy_forces_required_teeming');
            $table->integer('enemy_forces_shrouded');
            $table->integer('enemy_forces_shrouded_zul_gamux');
            $table->integer('timer_max_seconds');
        });
    }
}
