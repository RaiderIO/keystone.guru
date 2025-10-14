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
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->integer('mdt_polyline_id')->nullable()->after('polyline_id');

            $table->index(['mdt_polyline_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->dropColumn('mdt_polyline_id');
        });
    }
};
