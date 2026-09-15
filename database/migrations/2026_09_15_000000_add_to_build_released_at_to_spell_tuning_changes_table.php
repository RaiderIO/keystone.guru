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
        Schema::table('spell_tuning_changes', function (Blueprint $table) {
            // When `to_build` went live on the CDN, in UTC, per wago.tools; null when it could not be looked up
            $table->dateTime('to_build_released_at')->nullable()->after('to_build_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spell_tuning_changes', function (Blueprint $table) {
            $table->dropColumn('to_build_released_at');
        });
    }
};
