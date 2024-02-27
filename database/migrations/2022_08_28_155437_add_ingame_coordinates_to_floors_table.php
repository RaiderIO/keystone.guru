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
        Schema::table('floors', function (Blueprint $table) {
            $table->double('ingame_max_y', 10)->after('max_enemy_size')->default(0);
            $table->double('ingame_max_x', 10)->after('max_enemy_size')->default(0);

            $table->double('ingame_min_y', 10)->after('max_enemy_size')->default(0);
            $table->double('ingame_min_x', 10)->after('max_enemy_size')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->dropColumn('ingame_max_y');
            $table->dropColumn('ingame_max_x');

            $table->dropColumn('ingame_min_y');
            $table->dropColumn('ingame_min_x');
        });
    }
};
