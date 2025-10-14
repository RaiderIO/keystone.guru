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
        Schema::table('enemy_positions', function (Blueprint $table) {
            $table->integer('challenge_mode_run_id')->after('id');

            $table->index('challenge_mode_run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemy_positions', function (Blueprint $table) {
            $table->dropColumn('challenge_mode_run_id');
        });
    }
};
