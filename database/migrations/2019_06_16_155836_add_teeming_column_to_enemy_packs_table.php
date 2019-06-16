<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeemingColumnToEnemyPacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemy_packs', function (Blueprint $table) {
            $table->enum('teeming', ['visible', 'hidden'])->nullable()->after('floor_id');
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
            $table->dropColumn('teeming');
        });
    }
}
