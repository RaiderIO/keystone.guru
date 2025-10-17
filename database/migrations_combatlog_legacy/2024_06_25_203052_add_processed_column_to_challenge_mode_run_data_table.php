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
        Schema::table('challenge_mode_run_data', function (Blueprint $table) {
            $table->boolean('processed')->after('correlation_id')->default(0);

            $table->index(['processed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenge_mode_run_data', function (Blueprint $table) {
            $table->dropColumn('processed');
        });
    }
};
