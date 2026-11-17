<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dungeon_route_scheduled_publishes', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable()->after('dungeon_route_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dungeon_route_scheduled_publishes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
