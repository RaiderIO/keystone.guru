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
        Schema::table('dungeon_route_thumbnails', function (Blueprint $table) {
            // Distinguishes the standard (small) thumbnail from the larger hero-band variant.
            // Nullable-safe default keeps every pre-existing row a 'standard' thumbnail.
            $table->string('variant', 16)->default('standard')->after('custom')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_route_thumbnails', function (Blueprint $table) {
            $table->dropColumn('variant');
        });
    }
};
