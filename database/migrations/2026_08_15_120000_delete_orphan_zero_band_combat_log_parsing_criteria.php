<?php

use App\Models\CombatLog\CombatLogParsingCriterion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The band migration (2026_08_12_120000) backfilled pre-existing rows with
     * mythic_level_min = 0 and mythic_level_max = 0, expecting them to simply go inert since
     * no band the poller asks for starts at 0 (#3983). They didn't go unseen though: the admin
     * criteria page groups by whatever mythic_level_min values exist for today and renders every
     * group, so those rows surface as a phantom "Key level 0-0" band with real leftover counts
     * from before banding existed (#4038). mythic_level_max = 0 is never a value real band data
     * can have - the top band's max is null and every spread band's max is >= level_min - so this
     * signature is unambiguous and safe to delete outright.
     */
    public function up(): void
    {
        CombatLogParsingCriterion::query()
            ->where('mythic_level_min', 0)
            ->where('mythic_level_max', 0)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to restore - these rows carried no useful budget data
    }
};
