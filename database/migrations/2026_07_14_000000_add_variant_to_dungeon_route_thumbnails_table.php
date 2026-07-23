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
            $table->string('variant', 16)->default('standard')->after('custom');
        });

        // Split from the ADD COLUMN above into its own ALTER: adding a defaulted column on its own is
        // an instant, metadata-only change in MySQL 8, but adding an index in the same statement forces
        // the whole ALTER to run INPLACE (table rebuild) regardless. Keeping them separate shrinks the
        // rolling-deploy window where old and new code coexist against this table.
        Schema::table('dungeon_route_thumbnails', function (Blueprint $table) {
            $table->index('variant');
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
