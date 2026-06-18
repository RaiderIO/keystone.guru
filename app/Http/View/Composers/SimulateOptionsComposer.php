<?php

namespace App\Http\View\Composers;

use App\Models\Affix;
use App\Models\Season;
use App\Models\SimulationCraft\SimulationCraftRaidBuffs;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;
use Str;

class SimulateOptionsComposer
{
    public function __construct(
        private readonly ViewServiceInterface             $viewService,
        private readonly RequestViewContextInterface      $requestViewContext,
        private readonly SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ) {
    }

    public function compose(View $view): void
    {
        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());
        $shroudedBountyTypes = [];
        foreach (SimulationCraftRaidEventsOptions::ALL_SHROUDED_BOUNTY_TYPES as $bountyType) {
            $shroudedBountyTypes[$bountyType] = __(sprintf('view_common.modal.simulate.shrouded_bounty_types.%s', $bountyType));
        }
        $affixes = [];
        foreach (SimulationCraftRaidEventsOptions::ALL_AFFIXES as $affix) {
            $affixes[$affix] = __(sprintf('view_common.modal.simulateoptions.default.affixes_map.%s', $affix));
        }
        /** @var Season $currentSeason */
        $currentSeason     = $regionViewVariables['currentSeason'];
        $currentAffixGroup = $this->seasonAffixGroupService->getCurrentAffixGroup($currentSeason);
        $view->with('shroudedBountyTypes', $shroudedBountyTypes);
        $view->with('affixes', $affixes);
        $view->with('isShrouded', $currentAffixGroup?->hasAffix(Affix::AFFIX_SHROUDED) ?? false);
        $view->with('raidBuffsOptions', collect(SimulationCraftRaidBuffs::cases())->mapWithKeys(static fn(
            SimulationCraftRaidBuffs $raidBuff,
        ) => [
            $raidBuff->value => __(sprintf('view_common.modal.simulateoptions.default.raid_buffs_map.%s', Str::lower(Str::snake($raidBuff->name)))),
        ])->toArray());
    }
}
