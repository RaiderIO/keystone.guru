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
        Schema::table('mountable_areas', function (Blueprint $table) {
            $table->integer('speed')->after('floor_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mountable_areas', function (Blueprint $table) {
            $table->dropColumn('speed');
        });
    }
};
