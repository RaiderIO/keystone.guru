<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnemyForcesToNpcs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->integer('enemy_forces')->after('base_health');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn('enemy_forces');
        });
    }
}
