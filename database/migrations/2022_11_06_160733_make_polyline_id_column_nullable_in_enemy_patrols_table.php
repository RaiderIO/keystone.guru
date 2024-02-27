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
            $table->integer('polyline_id')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `enemy_patrols` SET `polyline_id` = null WHERE `polyline_id` <= 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemy_patrols', function (Blueprint $table) {
            $table->integer('polyline_id')->nullable(false)->default(-1)->change();
        });
    }
};
