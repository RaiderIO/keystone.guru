<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTeemingColumnToEnemyPatrolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->enum('teeming', ['visible', 'hidden'])->nullable()->after('polyline_id');
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
            $table->dropColumn('teeming');
        });
    }
}
