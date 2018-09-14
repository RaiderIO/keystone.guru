<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFactionToPacksAndPatrols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->enum('faction', ['any', 'alliance', 'horde'])->after('floor_id');
        });
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->enum('faction', ['any', 'alliance', 'horde'])->after('enemy_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->dropColumn('faction');
        });
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropColumn('faction');
        });
    }
}
