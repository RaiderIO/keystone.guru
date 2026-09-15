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
        Schema::table('spells', function (Blueprint $table) {
            // What this spell's damage and healing coefficients are multiplied by to become the amounts
            // the game shows. It belongs to the spell rather than to the dungeon it is cast in: measured
            // against the game's own numbers, one dungeon's spells can carry three different multipliers.
            //
            // Nothing in the client data derives it - it comes from the content tuning of whatever casts
            // the spell, which the client links from the creature, not from the spell. So it is measured
            // once per patch and shipped in the seeders; null means we could not, and the description
            // then shows no number rather than a made up one.
            // Anchored on `description`, which the first of these migrations adds - the format and values
            // columns arrive in a later one, so they are not there yet when this runs
            $table->double('damage_multiplier')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn('damage_multiplier');
        });
    }
};
