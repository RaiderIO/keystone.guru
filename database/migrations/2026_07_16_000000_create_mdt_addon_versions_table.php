<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reference table mapping each MDT addonVersion integer (the value MDT stamps into export strings,
     * e.g. 6120 for v6.1.20) to its upstream GitHub release date. Seeded from the committed
     * database/data/mdt/addon_versions.json. Used to attach imported MDT routes to the mapping version
     * matching the string's MDT era (#3380). Purely additive, so backward-compatible with running code.
     */
    public function up(): void
    {
        if (Schema::hasTable('mdt_addon_versions')) {
            return;
        }

        Schema::create('mdt_addon_versions', function (Blueprint $table) {
            $table->unsignedInteger('addon_version')->primary();
            $table->dateTime('released_at');
            $table->index('released_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdt_addon_versions');
    }
};
