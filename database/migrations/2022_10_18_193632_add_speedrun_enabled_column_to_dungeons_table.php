<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpeedrunEnabledColumnToDungeonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->boolean('speedrun_enabled')->after('timer_max_seconds')->default(0);

            $table->index('speedrun_enabled');
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
            $table->dropColumn('speedrun_enabled');

            $table->dropIndex('dungeons_speedrun_enabled_index');
        });
    }
}
