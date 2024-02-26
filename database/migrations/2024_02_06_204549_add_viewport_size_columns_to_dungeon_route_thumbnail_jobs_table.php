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
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->integer('viewport_height')->nullable()->after('status');
            $table->integer('viewport_width')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->dropColumn('viewport_height');
            $table->dropColumn('viewport_width');
        });
    }
};
