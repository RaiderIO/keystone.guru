<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShroudedToSeasonalTypeInEnemiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE enemies CHANGE COLUMN seasonal_type seasonal_type ENUM('awakened', 'inspiring', 'prideful', 'tormented', 'encrypted', 'mdt_placeholder', 'shrouded') NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE enemies CHANGE COLUMN seasonal_type seasonal_type ENUM('awakened', 'inspiring', 'prideful', 'tormented', 'encrypted', 'mdt_placeholder') NULL DEFAULT NULL");
    }
}
