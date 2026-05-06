<?php

use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DungeonRouteAffixGroup::where('affix_group_id', 139)->update(['affix_group_id' => 143]);
        DungeonRouteAffixGroup::where('affix_group_id', 140)->update(['affix_group_id' => 144]);
        DungeonRouteAffixGroup::where('affix_group_id', 141)->update(['affix_group_id' => 145]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DungeonRouteAffixGroup::where('affix_group_id', 143)->update(['affix_group_id' => 139]);
        DungeonRouteAffixGroup::where('affix_group_id', 144)->update(['affix_group_id' => 140]);
        DungeonRouteAffixGroup::where('affix_group_id', 145)->update(['affix_group_id' => 141]);
    }
};
