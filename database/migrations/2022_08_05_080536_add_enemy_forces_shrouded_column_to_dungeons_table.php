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
            $table->integer('enemy_forces_shrouded_zul_gamux')->default(0)->after('enemy_forces_required_teeming');
            $table->integer('enemy_forces_shrouded')->default(0)->after('enemy_forces_required_teeming');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('enemy_forces_shrouded_zul_gamux');
            $table->dropColumn('enemy_forces_shrouded');
        });
    }
};
