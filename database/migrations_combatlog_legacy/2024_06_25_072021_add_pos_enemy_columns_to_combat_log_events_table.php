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
        Schema::table('combat_log_events', function (Blueprint $table) {
            $table->double('pos_enemy_y')->nullable()->after('pos_y');
            $table->double('pos_enemy_x')->nullable()->after('pos_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combat_log_events', function (Blueprint $table) {
            $table->dropColumn('pos_enemy_x');
            $table->dropColumn('pos_enemy_y');
        });
    }
};
