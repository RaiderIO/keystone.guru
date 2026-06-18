<?php

namespace App\Http\View\Composers;

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Service\Expansion\ExpansionData;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class DiscoverAffixGroupComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        /** @var GameVersion $gameVersion */
        $gameVersion = $view->getData()['gameVersion'];
        // @TODO Should be loaded but it's not??
        $gameVersion->load(['expansion']);

        /** @var Expansion|null $expansion */
        $expansion = $view->getData()['expansion'] ?? null;
        /** @var ExpansionData $expansionsData */
        $expansionsData = $this->viewService->getExpansionsData($this->requestViewContext->getUserOrDefaultRegion())
            ->get(($expansion ?? $gameVersion->expansion)->shortname);
        $view->with('currentAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getCurrentAffixGroup());
        $view->with('nextAffixGroup', $expansionsData->getExpansionSeason()->getAffixGroups()->getNextAffixGroup());
    }
}
