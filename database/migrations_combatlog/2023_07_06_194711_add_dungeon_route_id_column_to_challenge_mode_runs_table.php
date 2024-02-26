<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('challenge_mode_runs', function (Blueprint $table) {
            $table->integer('dungeon_route_id')->after('dungeon_id');

            $table->index(['dungeon_route_id']);
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
            $table->dropColumn('dungeon_route_id');
        });
    }
};
