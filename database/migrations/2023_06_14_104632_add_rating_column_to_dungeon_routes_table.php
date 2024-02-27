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
            $table->integer('rating_count')->after('popularity')->default(0);
            $table->float('rating')->after('popularity')->nullable()->default(null);

            $table->index(['rating']);
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
            $table->dropColumn('rating_count');
            $table->dropColumn('rating');
        });
    }
};
