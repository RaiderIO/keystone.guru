<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->integer('thundering_clear_seconds')->nullable()->default(null)->after('affix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('simulation_craft_raid_events_options', function (Blueprint $table) {
            $table->dropColumn('thundering_clear_seconds');
        });
    }
};
