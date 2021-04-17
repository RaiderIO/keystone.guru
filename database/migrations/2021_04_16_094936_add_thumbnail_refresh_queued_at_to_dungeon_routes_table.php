<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThumbnailRefreshQueuedAtToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dateTime('thumbnail_refresh_queued_at')->default('1970-01-01 00:00:00')->after('pull_gradient_apply_always');

            $table->index('thumbnail_refresh_queued_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('thumbnail_refresh_queued_at');
        });
    }
}
