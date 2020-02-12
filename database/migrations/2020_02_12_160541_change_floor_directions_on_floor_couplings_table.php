<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeFloorDirectionsOnFloorCouplingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE floor_couplings CHANGE COLUMN direction direction ENUM('up', 'down', 'left', 'right') NOT NULL DEFAULT 'up'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE floor_couplings CHANGE COLUMN direction direction ENUM('equal', 'up', 'down') NOT NULL DEFAULT 'equal'");
    }
}
