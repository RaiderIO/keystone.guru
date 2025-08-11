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
            $table->index('name');
        });
        Schema::table('expansions', function (Blueprint $table) {
            $table->index('name');
        });
        Schema::table('npcs', function (Blueprint $table) {
            $table->index('name');
        });
        Schema::table('spells', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
        Schema::table('expansions', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
        Schema::table('spells', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
