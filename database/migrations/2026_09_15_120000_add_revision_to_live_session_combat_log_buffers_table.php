<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_session_combat_log_buffers', static function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(0)->after('last_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('live_session_combat_log_buffers', static function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
