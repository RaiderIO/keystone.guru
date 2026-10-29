<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_session_combat_log_buffers', static function (Blueprint $table) {
            $table->unsignedInteger('processed_revision')->nullable()->after('revision');
        });
    }

    public function down(): void
    {
        Schema::table('live_session_combat_log_buffers', static function (Blueprint $table) {
            $table->dropColumn('processed_revision');
        });
    }
};
