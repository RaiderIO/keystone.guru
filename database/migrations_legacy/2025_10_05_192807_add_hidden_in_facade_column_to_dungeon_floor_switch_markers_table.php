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
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->boolean('hidden_in_facade')->default(false)->after('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_floor_switch_markers', function (Blueprint $table) {
            $table->dropColumn('hidden_in_facade');
        });
    }
};
