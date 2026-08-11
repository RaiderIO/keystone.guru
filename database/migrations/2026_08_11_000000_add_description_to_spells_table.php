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
            // The raw DB2 template as shipped by the game client. Kept alongside the rendered text as its
            // provenance: it is what makes a changed description reviewable in a seeder diff, and what
            // tells you whether a description reads oddly because of the parser or because of the game.
            $table->text('description_template')->nullable()->after('name');
            // The description as a sprintf format with positional placeholders, and one entry per
            // placeholder saying what that number is. Damage and healing are coefficients of the content
            // the caster belongs to, so they have to stay separable from the sentence around them to be
            // recalculated per key level rather than fixed at import time.
            $table->text('description_format')->nullable()->after('description_template');
            $table->json('description_values')->nullable()->after('description_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn(['description_template', 'description_format', 'description_values']);
        });
    }
};
