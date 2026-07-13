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
            $table->dropColumn('release_id');
            $table->index('version');
        });

        Schema::dropIfExists('release_changelog_changes');
        Schema::dropIfExists('release_changelogs');
        Schema::dropIfExists('release_changelog_categories');
        Schema::dropIfExists('releases');
    }

    /**
     * Reverse the migrations. The dropped tables (and their seeded contents) are not restored - release
     * notes now live on GitHub Releases.
     */
    public function down(): void
    {
        Schema::table('release_report_logs', static function (Blueprint $table): void {
            $table->integer('release_id')->after('id')->default(0);
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });
    }
};
