<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_game_version_id')->nullable()->after('expansion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_versions', function (Blueprint $table) {
            $table->dropColumn('parent_game_version_id');
        });
    }
};
