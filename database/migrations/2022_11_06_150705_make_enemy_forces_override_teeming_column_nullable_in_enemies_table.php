<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('enemy_forces_override_teeming')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `enemies` SET `enemy_forces_override_teeming` = null WHERE `enemy_forces_override_teeming` <= 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('enemy_forces_override_teeming')->nullable(false)->default(-1)->change();
        });
    }
};
