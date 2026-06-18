<?php

namespace App\Http\View\Composers;

use App\Models\GameVersion\GameVersion;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class HeaderComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
        private readonly DungeonServiceInterface     $dungeonService,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();

        $view->with('activeExpansions', $this->viewService->getActiveExpansions());
        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($gameServerRegion));
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
        $view->with('allGameVersions', $this->viewService->getAllGameVersions());

        $userOrDefaultGameVersion = GameVersion::getUserOrDefaultGameVersion();
        $view->with('gameVersionDungeons', $this->dungeonService->getDungeonsForGameVersion($userOrDefaultGameVersion));
    }
}
