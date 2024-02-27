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
            // No need for an index - not searching on this column
            $table->integer('views_embed')->default(0)->after('views');
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
            $table->dropColumn('views_embed');
        });
    }
};
