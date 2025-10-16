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
        Schema::table('team_users', function (Blueprint $table) {
            $table->dropIndex(['user_id']);

            $table->index(['user_id', 'role']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_users', function (Blueprint $table) {
            $table->index('user_id');

            $table->dropIndex(['user_id', 'role']);
            $table->dropIndex('role');
        });
    }
};
