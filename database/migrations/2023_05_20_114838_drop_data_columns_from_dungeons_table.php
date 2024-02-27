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
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('enemy_forces_required');
            $table->dropColumn('enemy_forces_required_teeming');
            $table->dropColumn('enemy_forces_shrouded');
            $table->dropColumn('enemy_forces_shrouded_zul_gamux');
            $table->dropColumn('timer_max_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->integer('enemy_forces_required');
            $table->integer('enemy_forces_required_teeming');
            $table->integer('enemy_forces_shrouded');
            $table->integer('enemy_forces_shrouded_zul_gamux');
            $table->integer('timer_max_seconds');
        });
    }
};
