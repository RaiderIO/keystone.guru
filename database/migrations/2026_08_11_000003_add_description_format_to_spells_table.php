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
            // The description as a sprintf format with positional placeholders, and one entry per
            // placeholder saying what that number is. Damage and healing are coefficients of the content
            // the caster belongs to, so they have to stay separable from the sentence around them to be
            // recalculated per key level rather than fixed at import time.
            //
            // These supersede the plain `description` column added alongside description_template, which
            // is left in place: dropping a column in the same release that stops writing it would break
            // whichever containers are still running the old code mid-deploy.
            $table->text('description_format')->nullable()->after('description');
            $table->json('description_values')->nullable()->after('description_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spells', function (Blueprint $table) {
            $table->dropColumn(['description_format', 'description_values']);
        });
    }
};
