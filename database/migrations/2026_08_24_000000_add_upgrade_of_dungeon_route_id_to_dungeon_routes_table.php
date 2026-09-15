<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->unsignedInteger('upgrade_of_dungeon_route_id')->nullable()->after('clone_of');

            // Unique rather than a plain index: MySQL allows unlimited NULLs in a unique index, so it costs
            // nothing for the routes that aren't upgrade drafts, and it makes "one draft per original" a
            // database invariant instead of a service level hope - two racing findOrCreateDraft() calls give
            // the loser a duplicate key error rather than two drafts.
            $table->unique('upgrade_of_dungeon_route_id', 'dungeon_routes_upgrade_of_unique');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropUnique('dungeon_routes_upgrade_of_unique');
            $table->dropColumn('upgrade_of_dungeon_route_id');
        });
    }
};
