<?php

namespace App\Http\View\Composers;

use App\Models\Affix;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class SimulateComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface             $viewService,
        private RequestViewContextInterface      $requestViewContext,
        private SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ) {
    }

    public function compose(View $view): void
    {
        /** @var Season $currentSeason */
        $currentSeason     = $this->viewService->getCurrentSeasonForRegion($this->requestViewContext->getUserOrDefaultRegion());
        $currentAffixGroup = $this->seasonAffixGroupService->getCurrentAffixGroup($currentSeason);
        $view->with('isThundering', $currentAffixGroup?->hasAffix(Affix::AFFIX_THUNDERING) ?? false);
    }
}
