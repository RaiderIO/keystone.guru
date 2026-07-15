<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Contract half of the expand/contract for #3480 (see #3553). The additive work (re-keying the
     * release_report_logs announce-dedupe log to `version`) shipped in v15.4.5; this drops the now-unused
     * release_id column and the release_* tables one release later, once no running container references them.
     *
     * Guarded/idempotent because environments reach this migration in different states: staging already ran
     * the original destructive body of 2026_07_09_000000_drop_release_tables (tables gone, release_id gone),
     * while production (neutralised via an additive-only hotfix) and fresh databases still have them.
     */
    public function up(): void
    {
        if (Schema::hasColumn('release_report_logs', 'release_id')) {
            Schema::table('release_report_logs', static function (Blueprint $table): void {
                $table->dropColumn('release_id');
            });
        }

        Schema::dropIfExists('release_changelog_changes');
        Schema::dropIfExists('release_changelogs');
        Schema::dropIfExists('release_changelog_categories');
        Schema::dropIfExists('releases');
    }

    /**
     * Reverse the migration. The dropped release_* tables and their contents are not restored - release notes
     * now live on GitHub Releases. Only the release_report_logs.release_id column is re-added for symmetry.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('release_report_logs', 'release_id')) {
            Schema::table('release_report_logs', static function (Blueprint $table): void {
                $table->integer('release_id')->after('id')->default(0);
            });
        }
    }
};
