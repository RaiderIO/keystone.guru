<?php

namespace App\Http\View\Composers;

use App\Models\Season;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

readonly class CreateRouteFormComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
        private DungeonServiceInterface     $dungeonService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('currentDungeonContext', $this->dungeonService->getDungeonContext(Auth::user()));

        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();

        /** @var Season $currentSeason */
        $currentSeason = $this->viewService->getCurrentSeasonForRegion($gameServerRegion);

        // ViewService may hand every caller the same Season instance (it does in the local environment);
        // hiding relations on it would hide them from the affix picker, which reads `dungeons` and `expansion`
        $seasonLoader = static fn(?Season $season) => ($season === null ? null : clone $season)
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
        // ViewService::getNextSeasonForRegion() already gates on Season::active (#3868).
        $view->with('nextSeason', $seasonLoader($this->viewService->getNextSeasonForRegion($gameServerRegion)));
    }
}
