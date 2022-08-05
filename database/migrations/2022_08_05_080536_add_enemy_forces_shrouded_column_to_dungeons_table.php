<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnemyForcesShroudedColumnToDungeonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->integer('enemy_forces_shrouded_zul_gamux')->default(0)->after('enemy_forces_required_teeming');
            $table->integer('enemy_forces_shrouded')->default(0)->after('enemy_forces_required_teeming');
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
            $table->dropColumn('enemy_forces_shrouded_zul_gamux');
            $table->dropColumn('enemy_forces_shrouded');
        });
    }
}
