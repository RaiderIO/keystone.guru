<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE enemies CHANGE COLUMN seasonal_type seasonal_type
            ENUM('beguiling', 'awakened', 'inspiring', 'prideful', 'tormented', 'encrypted', 'mdt_placeholder', 'shrouded', 'shrouded_zul_gamux', 'no_shrouded')
            NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE enemies CHANGE COLUMN seasonal_type seasonal_type
            ENUM('awakened', 'inspiring', 'prideful', 'tormented', 'encrypted', 'mdt_placeholder', 'shrouded', 'shrouded_zul_gamux', 'no_shrouded')
            NULL DEFAULT NULL");
    }
};
