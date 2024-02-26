<?php

use Illuminate\Database\Migrations\Migration;

class UpdateMappingVersionIdInMapIconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::update('
            UPDATE `map_icons` SET mapping_version_id = null WHERE dungeon_route_id is not null
            '
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back
    }
}
