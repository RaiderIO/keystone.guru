<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameWidthAndHeightColumnsOnDungeonRouteThumbnailJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_route_thumbnail_jobs', function (Blueprint $table) {
            $table->renameColumn('width', 'image_width');
            $table->renameColumn('height', 'image_height');
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
            $table->renameColumn('image_width', 'width');
            $table->renameColumn('image_height', 'height');
        });
    }
}
