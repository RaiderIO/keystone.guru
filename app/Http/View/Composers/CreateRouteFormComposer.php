<?php

namespace App\Http\View\Composers;

use App\Models\Season;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class CreateRouteFormComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();

        /** @var Season $currentSeason */
        $currentSeason = $this->viewService->getCurrentSeasonForRegion($gameServerRegion);

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
        $view->with('currentSeason', $seasonLoader($currentSeason));
        $view->with('nextSeason', $seasonLoader($this->viewService->getNextSeasonForRegion($gameServerRegion)));
    }
}
