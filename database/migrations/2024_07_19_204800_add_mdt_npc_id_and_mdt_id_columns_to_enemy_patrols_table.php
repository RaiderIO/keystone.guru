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
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->integer('mdt_id')->nullable()->after('polyline_id');
            $table->integer('mdt_npc_id')->nullable()->after('polyline_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropColumn('mdt_id');
            $table->dropColumn('mdt_npc_id');
        });
    }
};
