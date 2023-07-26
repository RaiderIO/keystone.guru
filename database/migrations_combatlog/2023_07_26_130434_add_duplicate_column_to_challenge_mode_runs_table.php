<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDuplicateColumnToChallengeModeRunsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('challenge_mode_runs', function (Blueprint $table) {
            $table->boolean('duplicate')->after('total_time_ms')->default(0);

            $table->index(['duplicate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('challenge_mode_runs', function (Blueprint $table) {
            $table->dropColumn('duplicate');
        });
    }
}