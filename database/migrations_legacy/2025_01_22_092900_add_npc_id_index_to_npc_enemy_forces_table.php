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
        Schema::table('npc_enemy_forces', function (Blueprint $table) {
            $table->index('npc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npc_enemy_forces', function (Blueprint $table) {
            $table->dropIndex('npc_id');
        });
    }
};
