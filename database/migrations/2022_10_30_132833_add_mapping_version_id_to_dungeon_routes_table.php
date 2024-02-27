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
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->integer('mapping_version_id')->after('dungeon_id')->nullable();

            $table->index(['mapping_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn('mapping_version_id');
        });
    }
};
