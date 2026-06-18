<?php

namespace App\Http\View\Composers;

use App\Models\Affix;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class SimulateComposer
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
        /** @var Season $currentSeason */
        $currentSeason     = $regionViewVariables['currentSeason'];
        $currentAffixGroup = $this->seasonAffixGroupService->getCurrentAffixGroup($currentSeason);
        $view->with('isThundering', $currentAffixGroup?->hasAffix(Affix::AFFIX_THUNDERING) ?? false);
    }
}
