<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Additive flag marking a mapping version whose mapping diverges from what MDT ships - one we
     * created ourselves (mapping editor, copy) rather than imported from MDT. MDT import strings must
     * never resolve onto such a mapping version, since the enemies the string references are MDT's
     * (#4280). It is cleared only by the MDT import pipeline; for a dungeon MDT will never carry
     * (Den of Nalorakk's hand-authored packs) it simply stays set forever, which is the same behaviour.
     * Defaults to true - not-from-MDT is the safe assumption for any creation path, present or future,
     * that doesn't explicitly clear it, rather than requiring every such path to remember to set it
     * (Wotuu, #4282 review). Defaulted, so the change is backward-compatible with running code that
     * never reads it.
     */
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
