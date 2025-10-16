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
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('level_min')->default(null)->nullable()->change();
            $table->integer('level_max')->default(null)->nullable()->change();
        });

        DB::update('
            UPDATE `dungeon_routes`
                SET `level_min` = null, `level_max` = null
                WHERE `level_min` = 2 AND `level_max` >= 28
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('level_min')->default(2)->nullable(false)->change();
            $table->integer('level_max')->default(28)->nullable(false)->change();
        });

        DB::update('
            UPDATE `dungeon_routes`
                SET `level_min` = 2, `level_max` = 28
                WHERE `level_min` is null OR `level_max` is null
        ');
    }
};
