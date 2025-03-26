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
            // Now handled by context!
            $table->dropColumn('pos_enemy_x');
            $table->dropColumn('pos_enemy_y');

            $table->float('pos_grid_y')->after('pos_y');
            $table->float('pos_grid_x')->after('pos_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combat_log_events', function (Blueprint $table) {
            $table->dropColumn('pos_grid_y');
            $table->dropColumn('pos_grid_x');

            $table->float('pos_enemy_y')->after('pos_y');
            $table->float('pos_enemy_x')->after('pos_y');
        });
    }
};
