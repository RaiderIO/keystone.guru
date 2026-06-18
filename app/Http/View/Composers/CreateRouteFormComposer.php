<?php

namespace App\Http\View\Composers;

use App\Models\Season;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class CreateRouteFormComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());

        /** @var Season $currentSeason */
        $currentSeason = $regionViewVariables['currentSeason'];

        $seasonLoader = static fn(?Season $season) => $season
            ?->load([
                'seasonDungeons' => static function ($query) {
                    $query->without([
                        'season',
                        'dungeon',
                    ]);
                },
            ])
            ->makeHidden([
                'expansion',
                'dungeons',
            ])
            ->makeVisible(['seasonDungeons']);

        $view->with('routeKeyLevelFrom', $currentSeason->key_level_min);
        $view->with('routeKeyLevelTo', $currentSeason->key_level_max);
        $view->with('currentSeason', $seasonLoader($regionViewVariables['currentSeason']));
        $view->with('nextSeason', $seasonLoader($regionViewVariables['nextSeason']));
    }
}
