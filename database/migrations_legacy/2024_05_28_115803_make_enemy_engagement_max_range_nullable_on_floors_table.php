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
        Schema::table('floors', function (Blueprint $table) {
            $table->integer('enemy_engagement_max_range')->default(null)->nullable()->change();
            $table->integer('enemy_engagement_max_range_patrols')->default(null)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->integer('enemy_engagement_max_range')->default(150)->nullable(false)->change();
            $table->integer('enemy_engagement_max_range_patrols')->default(150)->nullable(false)->change();
        });
    }
};
