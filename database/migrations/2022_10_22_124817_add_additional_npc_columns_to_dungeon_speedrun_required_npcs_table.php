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
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->integer('npc5_id')->nullable()->after('npc_id');
            $table->integer('npc4_id')->nullable()->after('npc_id');
            $table->integer('npc3_id')->nullable()->after('npc_id');
            $table->integer('npc2_id')->nullable()->after('npc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_speedrun_required_npcs', function (Blueprint $table) {
            $table->dropColumn('npc2_id');
            $table->dropColumn('npc3_id');
            $table->dropColumn('npc4_id');
            $table->dropColumn('npc5_id');
        });
    }
};
