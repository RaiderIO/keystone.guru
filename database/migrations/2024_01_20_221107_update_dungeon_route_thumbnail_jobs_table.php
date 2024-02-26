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
        // I forgot to add the columns, but I already pushed the migration to add the table, doh
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->integer('quality')->nullable()->after('id');
            $table->integer('height')->nullable()->after('id');
            $table->integer('width')->nullable()->after('id');
            $table->enum('status', ['queued', 'completed', 'expired'])->default('queued')->after('id');
            $table->integer('floor_id')->after('id');
            $table->integer('dungeon_route_id')->after('id');

            $table->index('status');
            $table->index('dungeon_route_id');
            $table->index('floor_id');
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
            $table->dropColumn('dungeon_route_id');
            $table->dropColumn('floor_id');
            $table->dropColumn('status');
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('quality');
        });
    }
};
