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
        Schema::table('mapping_change_logs', function (Blueprint $table) {
            $table->integer('dungeon_id')->after('id')->nullable(true);

            $table->index(['dungeon_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('mapping_change_logs', function (Blueprint $table) {
            $table->dropColumn('dungeon_id');
        });
    }
};
