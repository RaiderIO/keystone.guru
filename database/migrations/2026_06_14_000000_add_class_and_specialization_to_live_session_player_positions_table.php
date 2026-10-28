<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_session_player_positions', static function (Blueprint $table) {
            $table->integer('class_id')->nullable()->after('floor_id');
            $table->integer('specialization_id')->nullable()->after('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('live_session_player_positions', static function (Blueprint $table) {
            $table->dropColumn(['class_id', 'specialization_id']);
        });
    }
};
