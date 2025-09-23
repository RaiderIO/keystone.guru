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
            $table->integer('expansion_id')->after('id');
            $table->index('expansion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_versions', function (Blueprint $table) {
            $table->dropColumn('expansion_id');
        });
    }
};
