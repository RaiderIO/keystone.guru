<?php

use App\Models\CombatLog\CombatLogParsingCriterion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            // MySQL treats NULLs in a unique index as distinct, so this does not stop two top band
            // rows (mythic_level_max null) for the same key. combatlog:pollruns runs
            // withoutOverlapping()->onOneServer(), so nothing creates those rows concurrently.
            $table->unique(
                ['combat_log_version', 'model_class', 'model_id', 'date', 'mythic_level_min', 'mythic_level_max'],
                'clpc_version_class_id_date_band_min_max_unique',
            );

            $table->dropUnique('clpc_version_class_id_date_band_unique');
        });
    }

    public function down(): void
    {
        // The narrower index cannot be restored while a top band and a spread band that start on the
        // same level both have a row for the day. These are daily counters the poller recreates on
        // demand, so keeping the oldest row per key loses nothing that matters.
        $survivingIds = CombatLogParsingCriterion::query()
            ->selectRaw('MIN(id) as id')
            ->groupBy('combat_log_version', 'model_class', 'model_id', 'date', 'mythic_level_min')
            ->pluck('id');

        CombatLogParsingCriterion::query()
            ->whereNotIn('id', $survivingIds)
            ->delete();

        Schema::table('combat_log_parsing_criteria', function (Blueprint $table) {
            $table->unique(
                ['combat_log_version', 'model_class', 'model_id', 'date', 'mythic_level_min'],
                'clpc_version_class_id_date_band_unique',
            );

            $table->dropUnique('clpc_version_class_id_date_band_min_max_unique');
        });
    }
};
