<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChallengeModeRunIdColumnToEnemyPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('combatlog')->table('enemy_positions', function (Blueprint $table) {
            $table->integer('challenge_mode_run_id')->after('id');

            $table->index('challenge_mode_run_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('combatlog')->table('enemy_positions', function (Blueprint $table) {
            $table->dropColumn('challenge_mode_run_id');
        });
    }
}
