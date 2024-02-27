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
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->integer('timer_max_seconds')->after('version')->default(0);
            $table->integer('enemy_forces_shrouded_zul_gamux')->after('version')->nullable()->default(null);
            $table->integer('enemy_forces_shrouded')->after('version')->nullable()->default(null);
            $table->integer('enemy_forces_required_teeming')->after('version')->nullable()->default(null);
            $table->integer('enemy_forces_required')->after('version')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->dropColumn('enemy_forces_required');
            $table->dropColumn('enemy_forces_required_teeming');
            $table->dropColumn('enemy_forces_shrouded');
            $table->dropColumn('enemy_forces_shrouded_zul_gamux');
            $table->dropColumn('timer_max_seconds');
        });
    }
};
