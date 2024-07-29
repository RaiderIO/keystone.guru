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
            // Saved as a string so that we keep the numbers exactly the same and don't run into floating point issues
            $table->string('mdt_y')->nullable()->after('mdt_scale');
            $table->string('mdt_x')->nullable()->after('mdt_scale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enemies', function (Blueprint $table) {
            $table->dropColumn('mdt_y');
            $table->dropColumn('mdt_x');
        });
    }
};
