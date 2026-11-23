<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->string('ai_source', 16)->nullable()->after('published_state_id');
            $table->string('ai_client_name', 255)->nullable()->after('ai_source');
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            $table->dropColumn(['ai_source', 'ai_client_name']);
        });
    }
};
