<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('mapping_versions', 'mdt_changes_pending')) {
            return;
        }

        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->boolean('mdt_changes_pending')->default(true)->after('mdt_addon_version');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('mapping_versions', 'mdt_changes_pending')) {
            return;
        }

        Schema::table('mapping_versions', function (Blueprint $table) {
            $table->dropColumn('mdt_changes_pending');
        });
    }
};
