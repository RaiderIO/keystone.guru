<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Release notes moved to GitHub Releases as the single source of truth (#3480). The announce dedupe log
     * survives, re-keyed by version instead of release_id, so re-running release:report never double-posts.
     */
    public function up(): void
    {
        Schema::table('release_report_logs', static function (Blueprint $table): void {
            $table->string('version')->after('release_id')->default('');
        });

        // Backfill the version from the releases table while it still exists
        DB::table('release_report_logs')
            ->join('releases', 'releases.id', '=', 'release_report_logs.release_id')
            ->update(['release_report_logs.version' => DB::raw('releases.version')]);

        Schema::table('release_report_logs', static function (Blueprint $table): void {
            $table->index('version');
        });

        // The destructive drops (release_id column + release_* tables) are deferred to the follow-up
        // migration 2026_07_14_000000_drop_release_tables_followup so that old still-running containers
        // keep working during the non-atomic deploy window (expand/contract, see #3553).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('release_report_logs', static function (Blueprint $table): void {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });
    }
};
