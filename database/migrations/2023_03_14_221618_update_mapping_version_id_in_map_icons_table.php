<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
    public function down(): void
    {
        // No going back
    }
};
