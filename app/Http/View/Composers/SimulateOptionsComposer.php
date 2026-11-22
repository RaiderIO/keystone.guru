<?php

namespace App\Http\View\Composers;

use App\Models\SimulationCraft\SimulationCraftRaidBuffs;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\View\View;
use Str;

readonly class SimulateOptionsComposer implements ViewComposerInterface
{
    public function compose(View $view): void
    {
        $affixes = [];
        foreach (SimulationCraftRaidEventsOptions::ALL_AFFIXES as $affix) {
            $affixes[$affix] = __(sprintf('view_common.modal.simulateoptions.default.affixes_map.%s', $affix));
        }
        $view->with('affixes', $affixes);
        $view->with('fortifiedHealthIncreasePercent', (int)round((config('keystoneguru.keystone.affix_scaling_factor.fortified') - 1) * 100));
        $view->with('tyrannicalHealthIncreasePercent', (int)round((config('keystoneguru.keystone.affix_scaling_factor.tyrannical') - 1) * 100));
        $view->with('raidBuffsOptions', collect(SimulationCraftRaidBuffs::cases())->mapWithKeys(static fn(
            SimulationCraftRaidBuffs $raidBuff,
        ) => [
            $raidBuff->value => __(sprintf('view_common.modal.simulateoptions.default.raid_buffs_map.%s', Str::lower(Str::snake($raidBuff->name)))),
        ])->toArray());
    }
}
