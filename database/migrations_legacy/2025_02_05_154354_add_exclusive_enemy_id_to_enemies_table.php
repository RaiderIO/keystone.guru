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
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('exclusive_enemy_id')->after('mdt_npc_id')->nullable();
            $table->index('exclusive_enemy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropColumn('exclusive_enemy_id');
        });
    }
};
