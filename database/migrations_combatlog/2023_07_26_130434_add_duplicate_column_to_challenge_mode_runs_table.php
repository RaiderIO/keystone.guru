<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('challenge_mode_runs', function (Blueprint $table) {
            $table->boolean('duplicate')->after('total_time_ms')->default(0);

            $table->index(['duplicate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenge_mode_runs', function (Blueprint $table) {
            $table->dropColumn('duplicate');
        });
    }
};
