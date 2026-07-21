<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Additive column recording the MDT addon version a mapping version was imported from (the newest
     * MDT version live at import time, e.g. 6120 for MDT v6.1.20). Used to attach imported MDT routes
     * to the mapping version that matches the string's MDT era (#3380). Nullable/backfilled, so the
     * change is backward-compatible with running code that never reads it.
     */
    public function up(): void
    {
        if (Schema::hasColumn('mapping_versions', 'mdt_addon_version')) {
            return;
        }

        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->unsignedInteger('mdt_addon_version')->nullable()->after('mdt_mapping_hash');
            $table->index('mdt_addon_version');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('mapping_versions', 'mdt_addon_version')) {
            return;
        }

        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->dropIndex(['mdt_addon_version']);
            $table->dropColumn('mdt_addon_version');
        });
    }
};
