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
        Schema::table('enemies', function (Blueprint $table) {
            $table->integer('enemy_forces_checkpoint_id')->default(null)->nullable()->after('enemy_patrol_id');

            $table->index(['enemy_forces_checkpoint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropIndex(['enemy_forces_checkpoint_id']);

            $table->dropColumn('enemy_forces_checkpoint_id');
        });
    }
};
