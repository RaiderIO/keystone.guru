<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * When the hourly sync last actually saw this link's Patreon member, and what it decided (#4373).
     */
    public function up(): void
    {
        if (Schema::hasColumn('patreon_user_links', 'last_seen_at')) {
            return;
        }

        Schema::table('patreon_user_links', function (Blueprint $table) {
            $table->dateTime('last_seen_at')->nullable()->after('expires_at');
            $table->unsignedTinyInteger('last_sync_result')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('patreon_user_links', 'last_seen_at')) {
            return;
        }

        Schema::table('patreon_user_links', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'last_sync_result']);
        });
    }
};
