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
        Schema::table('character_races', function (Blueprint $table) {
            $table->integer('race_id')->after('id');
            $table->index('race_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_races', function (Blueprint $table) {
            $table->dropColumn('race_id');
        });
    }
};
