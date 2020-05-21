<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAwakenedAggressivenessToNpcs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE npcs CHANGE COLUMN aggressiveness aggressiveness ENUM('neutral', 'unfriendly', 'aggressive', 'friendly', 'awakened') NOT NULL DEFAULT 'aggressive'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // If above fails then this will fail as well, *shrug*
        DB::statement("ALTER TABLE npcs CHANGE COLUMN aggressiveness aggressiveness ENUM('neutral', 'unfriendly', 'aggressive', 'friendly') NOT NULL DEFAULT 'aggressive'");
    }
}
