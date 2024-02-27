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
            UPDATE `dungeon_routes`
                SET `dungeon_routes`.`mapping_version_id` = `dungeon_routes`.`dungeon_id`
            WHERE `dungeon_routes`.`mapping_version_id` IS NULL;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // No going back!
    }
};
