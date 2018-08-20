<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveGameIdFromNpcs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Yea, should fill id with the current game_id but that's not really important atm, all tables are empty
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn('game_id');
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
            $table->integer('game_id')->after('classification_id');
        });
    }
}
