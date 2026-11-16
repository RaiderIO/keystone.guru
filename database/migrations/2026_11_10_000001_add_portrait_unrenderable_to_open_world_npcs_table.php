<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('open_world_npcs', function (Blueprint $table) {
            $table->boolean('portrait_unrenderable')->default(false)->after('has_portrait');
        });
    }

    public function down(): void
    {
        Schema::table('open_world_npcs', function (Blueprint $table) {
            $table->dropColumn('portrait_unrenderable');
        });
    }
};
