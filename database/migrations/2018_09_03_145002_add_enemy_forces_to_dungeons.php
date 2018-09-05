<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnemyForcesToDungeons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->integer('enemy_forces_required')->after('name');
            $table->integer('enemy_forces_required_teeming')->after('enemy_forces_required');
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
            $table->dropColumn('enemy_forces_required_teeming');
            $table->dropColumn('enemy_forces_required');
        });
    }
}
