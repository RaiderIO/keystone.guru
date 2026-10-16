<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const string STATUS_WITH_ERROR = "enum('queued','completed','expired','error')";

    private const string STATUS_WITHOUT_ERROR = "enum('queued','completed','expired')";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(sprintf("ALTER TABLE `dungeon_route_thumbnail_jobs` MODIFY `status` %s NOT NULL DEFAULT 'queued'", self::STATUS_WITH_ERROR));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('dungeon_route_thumbnail_jobs')->where('status', 'error')->update(['status' => 'expired']);

        DB::statement(sprintf("ALTER TABLE `dungeon_route_thumbnail_jobs` MODIFY `status` %s NOT NULL DEFAULT 'queued'", self::STATUS_WITHOUT_ERROR));
    }
};
