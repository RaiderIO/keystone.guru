<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            // Used by resetAllForToday() and the criteria() controller query
            $table->index('date', 'clpc_date_index');

            // Used by getDefaultThreshold(): WHERE model_class = ? ORDER BY date DESC
            $table->index(['model_class', 'date'], 'clpc_model_class_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            $table->dropIndex('clpc_date_index');
            $table->dropIndex('clpc_model_class_date_index');
        });
    }
};
