<?php

use App\Models\CombatLog\CombatLogParsingCriterion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            // The key level band this budget applies to. mythic_level_max is null for the
            // top band, which is open ended and never budgeted. Pre-existing rows keep 0,
            // which no longer matches any band the poller asks for and simply goes inert.
            $table->unsignedInteger('mythic_level_min')->default(0)->after('model_id');
            $table->unsignedInteger('mythic_level_max')->nullable()->after('mythic_level_min');
        });

        // A null mythic_level_max marks the always-parsed top band, so pre-existing rows must not
        // be left with one: the admin page would render today's rows as a top band and hide their
        // threshold inputs until tomorrow's rows are created.
        CombatLogParsingCriterion::query()
            ->whereNull('mythic_level_max')
            ->update(['mythic_level_max' => 0]);

        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            $table->dropUnique('clpc_version_model_class_model_id_date_unique');

            $table->unique(
                ['combat_log_version', 'model_class', 'model_id', 'date', 'mythic_level_min'],
                'clpc_version_class_id_date_band_unique',
            );

            // Used by getDefaultThreshold(): WHERE model_class = ? AND mythic_level_min = ? ORDER BY date DESC.
            // Replaces clpc_model_class_date_index, whose lookup it serves by prefix - only the
            // ordering falls back to a filesort, which this table is far too small to care about.
            $table->index(['model_class', 'mythic_level_min', 'date'], 'clpc_model_class_band_date_index');
            $table->dropIndex('clpc_model_class_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            $table->index(['model_class', 'date'], 'clpc_model_class_date_index');
            $table->dropIndex('clpc_model_class_band_date_index');

            $table->dropUnique('clpc_version_class_id_date_band_unique');
            $table->unique(
                ['combat_log_version', 'model_class', 'model_id', 'date'],
                'clpc_version_model_class_model_id_date_unique',
            );
        });

        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            $table->dropColumn(['mythic_level_min', 'mythic_level_max']);
        });
    }
};
