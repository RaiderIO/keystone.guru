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
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('npc_id')->nullable()->default(null)->change();
        });

        DB::update('UPDATE `enemies` SET `npc_id` = null WHERE `npc_id` <= 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('npc_id')->nullable(false)->default(-1)->change();
        });
    }
};
