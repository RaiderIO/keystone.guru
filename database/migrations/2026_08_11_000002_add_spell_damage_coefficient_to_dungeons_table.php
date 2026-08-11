<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            // What a creature damage or healing coefficient is multiplied by inside this dungeon. The
            // game derives it from the dungeon's content tuning; we calibrate it once per patch and ship
            // it, because nothing in the client data links a spell to the content it is cast in.
            $table->double('spell_damage_coefficient')->nullable()->after('speedrun_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('spell_damage_coefficient');
        });
    }
};
