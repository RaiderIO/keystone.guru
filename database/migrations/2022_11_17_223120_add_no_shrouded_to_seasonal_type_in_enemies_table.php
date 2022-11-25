<?php

use Illuminate\Database\Migrations\Migration;

class AddNoShroudedToSeasonalTypeInEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE enemies CHANGE COLUMN seasonal_type seasonal_type ENUM('awakened', 'inspiring', 'prideful', 'tormented', 'encrypted', 'mdt_placeholder', 'shrouded', 'shrouded_zul_gamux', 'no_shrouded') NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE enemies CHANGE COLUMN seasonal_type seasonal_type ENUM('awakened', 'inspiring', 'prideful', 'tormented', 'encrypted', 'mdt_placeholder', 'shrouded', 'shrouded_zul_gamux') NULL DEFAULT NULL");
    }
}
